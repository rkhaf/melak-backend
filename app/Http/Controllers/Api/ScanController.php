<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessScanJob;
use App\Models\FishScan;
use App\Models\UserPond;
use App\Support\ImageStorage;
use App\Support\KecamatanResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ImageStorage $storage) {}

    /**
     * POST /api/scans (API.md §4.1, FR-02.1) — 202, diproses async.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'file|image|mimes:jpeg,jpg,png,webp|max:10240',
            'fish_type' => 'nullable|string|max:64',
            'pond_id' => ['nullable', 'uuid', Rule::exists('user_ponds', 'id')->where('user_id', $request->user()->id)],
            'symptoms_desc' => 'nullable|string|max:1000',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'images.required' => 'Minimal 1 foto wajib diunggah.',
            'images.min' => 'Minimal 1 foto wajib diunggah.',
            'images.max' => 'Maksimal 5 foto per scan.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran maksimal 10 MB per foto.',
            'pond_id.exists' => 'Kolam tidak ditemukan.',
        ]);

        // Lokasi kecamatan untuk peta sebaran: koordinat GPS scan lebih dulu,
        // fallback ke koordinat kolam. Tanpa keduanya → kecamatan null
        // (titik tetap tampil di heatmap lewat kolom ini setelah backfill).
        $lat = isset($data['location_lat']) ? (float) $data['location_lat'] : null;
        $lng = isset($data['location_lng']) ? (float) $data['location_lng'] : null;

        if ($lat === null && $lng === null && ! empty($data['pond_id'])) {
            $pond = UserPond::find($data['pond_id']);
            $lat = $pond?->location_lat !== null ? (float) $pond->location_lat : null;
            $lng = $pond?->location_lng !== null ? (float) $pond->location_lng : null;
        }

        $scan = FishScan::create([
            'user_id' => $request->user()->id,
            'pond_id' => $data['pond_id'] ?? null,
            'fish_type' => $data['fish_type'] ?? null,
            'symptoms_desc' => $data['symptoms_desc'] ?? null,
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'kecamatan' => app(KecamatanResolver::class)->resolve($lat, $lng),
            'status' => 'processing',
        ]);

        foreach ($request->file('images') as $index => $file) {
            $stored = $this->storage->storeScanImage($file, $scan->id, $index);

            $scan->images()->create([
                's3_key' => $stored['s3_key'],
                's3_url' => $stored['s3_url'],
                'is_primary' => $index === 0,
                'order_index' => $index,
            ]);
        }

        ProcessScanJob::dispatch($scan->id);

        return $this->accepted([
            'scan_id' => $scan->id,
            'status' => 'processing',
            'images_count' => count($request->file('images')),
            'estimated_seconds' => (int) config('ngingu.scan.estimated_seconds', 10),
        ], 'Scan diterima, sedang diproses');
    }

    /**
     * GET /api/scans (API.md §4.2, FR-04.1.2).
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'pond_id' => 'sometimes|uuid',
            'fish_type' => 'sometimes|string|max:64',
            'disease_id' => 'sometimes|integer',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date',
        ]);

        $query = FishScan::query()
            ->where('user_id', $request->user()->id)
            ->with(['pond:id,name', 'result.disease:id,name,slug,severity', 'images'])
            ->when(! empty($data['pond_id']), fn ($q) => $q->where('pond_id', $data['pond_id']))
            ->when(! empty($data['fish_type']), fn ($q) => $q->where('fish_type', $data['fish_type']))
            ->when(! empty($data['disease_id']), fn ($q) => $q->whereHas('result', fn ($r) => $r->where('disease_id', $data['disease_id'])))
            ->when(! empty($data['from']), fn ($q) => $q->where('created_at', '>=', $data['from']))
            ->when(! empty($data['to']), fn ($q) => $q->where('created_at', '<=', $data['to']))
            ->orderByDesc('created_at');

        $page = $query->paginate((int) ($data['per_page'] ?? 15))->withQueryString();

        return $this->paginated($page->through(fn (FishScan $scan) => $this->listPayload($scan)));
    }

    /**
     * GET /api/scans/{id} (API.md §4.3) — long-polling `?wait=`.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $scan = FishScan::with(['pond:id,name', 'result.disease:id,name,slug,severity', 'images'])
            ->find($id);

        if (! $scan) {
            return $this->fail('Scan tidak ditemukan.', 404);
        }

        if ($scan->user_id !== $request->user()->id) {
            return $this->fail('Anda tidak memiliki akses ke scan ini.', 403);
        }

        $waitDefault = (int) config('ngingu.scan.wait_default', 120);
        $waitMax = (int) config('ngingu.scan.wait_max', 300);
        $wait = min(max((int) $request->input('wait', $waitDefault), 0), $waitMax);
        $gradcam = $request->boolean('gradcam');

        $deadline = microtime(true) + $wait;

        while (! $scan->isFinished() && microtime(true) < $deadline) {
            set_time_limit(max($waitMax + 10, 310));
            sleep(1);
            $scan->refresh();
        }

        $payload = $this->detailPayload($scan, $gradcam);

        if (! $scan->isFinished()) {
            $payload['poll_after_ms'] = 2000;
        }

        return $this->ok($payload);
    }

    /**
     * DELETE /api/scans/{id} (API.md §4.4, FR-04.1.4).
     */
    public function destroy(Request $request, string $id): Response
    {
        $scan = FishScan::find($id);

        if (! $scan) {
            return $this->fail('Scan tidak ditemukan.', 404);
        }

        if ($scan->user_id !== $request->user()->id) {
            return $this->fail('Anda tidak memiliki akses ke scan ini.', 403);
        }

        foreach ($scan->images as $image) {
            $this->storage->delete($image->s3_key);
        }

        $scan->delete();

        return $this->noContent();
    }

    /**
     * GET /api/scans/export (API.md §4.6, FR-04.1.5) — csv | pdf.
     */
    public function export(Request $request): StreamedResponse|Response
    {
        $data = $request->validate([
            'format' => 'sometimes|in:csv,pdf',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date',
            'pond_id' => 'sometimes|uuid',
        ]);

        $scans = FishScan::query()
            ->where('user_id', $request->user()->id)
            ->with(['pond:id,name', 'result.disease:id,name,severity'])
            ->when(! empty($data['pond_id']), fn ($q) => $q->where('pond_id', $data['pond_id']))
            ->when(! empty($data['from']), fn ($q) => $q->where('created_at', '>=', $data['from']))
            ->when(! empty($data['to']), fn ($q) => $q->where('created_at', '<=', $data['to']))
            ->orderByDesc('created_at')
            ->get();

        $rows = $scans->map(fn (FishScan $scan) => $this->exportRow($scan))->values();

        if (($data['format'] ?? 'csv') === 'pdf') {
            $pdf = Pdf::loadView('scans.pdf.history', [
                'rows' => $rows,
                'generatedAt' => now(),
            ]);

            return $pdf->download('riwayat-scan-'.now()->format('Ymd-His').'.pdf');
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'kolam', 'jenis_ikan', 'penyakit', 'confidence', 'tingkat', 'tindakan']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['tanggal'],
                    $row['kolam'],
                    $row['jenis_ikan'],
                    $row['penyakit'],
                    $row['confidence'],
                    $row['tingkat'],
                    $row['tindakan'],
                ]);
            }

            fclose($out);
        }, 'riwayat-scan-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/scans/{id}/export-pdf (API.md §4.5, FR-03.2.6).
     */
    public function exportPdf(Request $request, string $id): Response
    {
        $scan = FishScan::with(['pond:id,name', 'result.disease', 'images'])->find($id);

        if (! $scan) {
            return $this->fail('Scan tidak ditemukan.', 404);
        }

        if ($scan->user_id !== $request->user()->id) {
            return $this->fail('Anda tidak memiliki akses ke scan ini.', 403);
        }

        $disease = $scan->result?->disease;

        $pdf = Pdf::loadView('scans.pdf.result', [
            'scan' => $scan,
            'disease' => $disease,
            'result' => $scan->result,
            'generatedAt' => now(),
        ]);

        return $pdf->download('hasil-scan-'.$scan->id.'.pdf');
    }

    private function listPayload(FishScan $scan): array
    {
        $primary = $scan->images->firstWhere('is_primary') ?? $scan->images->first();

        return [
            'scan_id' => $scan->id,
            'thumbnail_url' => $primary ? $this->storage->url($this->storage->thumbKey($primary->s3_key)) : null,
            'fish_type' => $scan->fish_type,
            'pond' => $scan->pond ? ['id' => $scan->pond->id, 'name' => $scan->pond->name] : null,
            'disease' => $scan->result?->disease ? [
                'id' => $scan->result->disease->id,
                'name' => $scan->result->disease->name,
                'slug' => $scan->result->disease->slug,
                'severity' => $scan->result->disease->severity,
            ] : null,
            'confidence' => $scan->result?->confidence_score,
            'status' => $scan->status,
            'created_at' => $scan->created_at?->toIso8601String(),
        ];
    }

    private function detailPayload(FishScan $scan, bool $gradcam): array
    {
        $result = $scan->result;
        $disease = $result?->disease;

        $payload = [
            'scan_id' => $scan->id,
            'status' => $scan->status,
            'fish_type' => $scan->fish_type,
            'pond' => $scan->pond ? ['id' => $scan->pond->id, 'name' => $scan->pond->name] : null,
            'images' => $scan->images->map(fn ($image) => [
                'url' => $image->s3_url,
                'is_primary' => (bool) $image->is_primary,
                'gradcam_url' => $gradcam ? $image->gradcam_url : null,
            ])->values()->all(),
            'disease' => null,
            'top_predictions' => null,
            'low_confidence_warning' => null,
            'gradcam_url' => null,
            'processing_time_ms' => null,
            'model_version' => null,
            'treatment_url' => null,
            'created_at' => $scan->created_at?->toIso8601String(),
        ];

        if ($scan->status === 'failed') {
            $payload['error'] = [
                'code' => $scan->error_code ?? 'ML_ERROR',
                'message' => $scan->error_message ?? 'Prediksi gagal diproses. Coba lagi.',
            ];

            return $payload;
        }

        if ($scan->status === 'done' && $result) {
            $primary = $scan->images->firstWhere('is_primary') ?? $scan->images->first();

            $payload['disease'] = $disease ? [
                'id' => $disease->id,
                'name' => $disease->name,
                'slug' => $disease->slug,
                'severity' => $disease->severity,
                'confidence' => (float) $result->confidence_score,
            ] : null;
            $payload['top_predictions'] = $result->top_predictions;
            $payload['low_confidence_warning'] = (bool) $result->low_confidence_warning;
            $payload['gradcam_url'] = $gradcam ? ($result->top_predictions ? ($primary?->gradcam_url) : null) : null;
            $payload['processing_time_ms'] = $result->processing_time_ms;
            $payload['model_version'] = $result->model_version;
            $payload['treatment_url'] = $disease ? '/diseases/'.$disease->slug.'#penanganan' : null;
        }

        return $payload;
    }

    private function exportRow(FishScan $scan): array
    {
        $disease = $scan->result?->disease;
        $confidence = $scan->result?->confidence_score;

        return [
            'tanggal' => $scan->created_at?->format('d/m/Y H:i'),
            'kolam' => $scan->pond?->name ?? '-',
            'jenis_ikan' => $scan->fish_type ?? '-',
            'penyakit' => $disease?->name ?? ($scan->status === 'failed' ? 'Gagal' : 'Belum selesai'),
            'confidence' => $confidence !== null ? number_format((float) $confidence, 1).'%' : '-',
            'tingkat' => $disease?->severity ?? '-',
            'tindakan' => $disease ? '/diseases/'.$disease->slug.'#penanganan' : '-',
        ];
    }
}
