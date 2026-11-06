<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FishScan;
use App\Models\ScanResult;
use App\Models\UserPond;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kolam / tambak (API.md §7.2 — §7.6, FR-08.1 — FR-08.2).
 */
class PondController extends Controller
{
    use ApiResponse, ScanPresenter;

    /**
     * GET /api/ponds (API.md §7.2).
     */
    public function index(Request $request): JsonResponse
    {
        $ponds = UserPond::where('user_id', $request->user()->id)
            ->withCount(['scans as scans_count'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($ponds->map(fn (UserPond $pond) => $this->pondPayload($pond))->values());
    }

    /**
     * GET /api/ponds/{id} (API.md §7.14) — detail satu tambak milik sendiri.
     * Dipakai form "Ubah Tambak" & layar laporan kesehatan untuk prefill
     * data segar (bukan dari argumen route).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $pond = $this->ownPond($request, $id);

        if (! $pond) {
            return $this->fail('Kolam tidak ditemukan.', 404);
        }

        return $this->ok($this->pondPayload($pond));
    }

    /**
     * POST /api/ponds (API.md §7.3).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'area_m2' => 'nullable|numeric|min:1|max:1000000',
            'capacity' => 'nullable|integer|min:1|max:10000000',
            'fish_types' => 'sometimes|array|max:10',
            'fish_types.*' => 'string|max:30',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama kolam wajib diisi.',
            'name.min' => 'Nama kolam minimal 2 karakter.',
            'fish_types.max' => 'Maksimal 10 jenis ikan.',
        ]);

        $pond = UserPond::create([
            'user_id' => $request->user()->id,
            'health_score' => 100,
            ...$data,
        ]);

        return $this->created($this->pondPayload($pond), 'Kolam berhasil ditambahkan');
    }

    /**
     * PUT /api/ponds/{id} (API.md §7.4).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pond = $this->ownPond($request, $id);

        if (! $pond) {
            return $this->fail('Kolam tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|min:2|max:80',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'area_m2' => 'nullable|numeric|min:1|max:1000000',
            'capacity' => 'nullable|integer|min:1|max:10000000',
            'fish_types' => 'sometimes|array|max:10',
            'fish_types.*' => 'string|max:30',
            'description' => 'nullable|string|max:500',
        ]);

        $pond->fill($data);
        $pond->save();

        return $this->ok($this->pondPayload($pond), 'Kolam berhasil diperbarui');
    }

    /**
     * DELETE /api/ponds/{id} (API.md §7.5).
     */
    public function destroy(Request $request, string $id): Response
    {
        $pond = $this->ownPond($request, $id);

        if (! $pond) {
            return $this->fail('Kolam tidak ditemukan.', 404);
        }

        $pond->delete();

        return $this->noContent();
    }

    /**
     * GET /api/ponds/{id}/health-report (API.md §7.6, FR-04.2).
     */
    public function healthReport(Request $request, string $id): JsonResponse
    {
        $pond = $this->ownPond($request, $id);

        if (! $pond) {
            return $this->fail('Kolam tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'period' => 'sometimes|in:7d,30d,90d',
        ]);

        $days = match ($data['period'] ?? '7d') {
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $scans = FishScan::with(['result.disease'])
            ->where('pond_id', $pond->id)
            ->where('status', 'done')
            ->where('created_at', '>=', now()->startOfDay()->subDays($days - 1))
            ->get();

        $pond->health_score = $this->computeHealthScore($pond);
        $pond->save();

        return $this->ok([
            'pond' => ['id' => $pond->id, 'name' => $pond->name],
            'health_score' => $pond->health_score,
            'trend' => $this->trend($scans, $days),
            'repeat_disease_alert' => $this->repeatDiseaseAlert($pond),
            'last_scan' => $this->lastScan($pond),
        ]);
    }

    private function ownPond(Request $request, string $id): ?UserPond
    {
        return UserPond::where('user_id', $request->user()->id)->find($id);
    }

    private function pondPayload(UserPond $pond): array
    {
        return [
            'id' => $pond->id,
            'name' => $pond->name,
            'description' => $pond->description,
            'location_lat' => $pond->location_lat !== null ? (float) $pond->location_lat : null,
            'location_lng' => $pond->location_lng !== null ? (float) $pond->location_lng : null,
            'location_label' => $pond->location_label,
            'area_m2' => $pond->area_m2 !== null ? (float) $pond->area_m2 : null,
            'capacity' => $pond->capacity !== null ? (int) $pond->capacity : null,
            'fish_types' => $pond->fish_types ?? [],
            'health_score' => (int) $pond->health_score,
            'scans_count' => (int) ($pond->scans_count ?? $pond->scans()->count()),
            'created_at' => $pond->created_at?->toIso8601String(),
        ];
    }

    /**
     * FR-04.2.2 — skor 0-100 dari frekuensi & keparahan penyakit (30 hari terakhir).
     */
    private function computeHealthScore(UserPond $pond): int
    {
        $penalties = ['ringan' => 4, 'sedang' => 8, 'berat' => 15, 'kritis' => 25];

        $results = ScanResult::query()
            ->whereIn('scan_id', fn ($q) => $q
                ->select('id')
                ->from('fish_scans')
                ->where('pond_id', $pond->id)
                ->where('status', 'done')
                ->where('created_at', '>=', now()->subDays(30)))
            ->with('disease:id,severity')
            ->get();

        $score = 100;

        foreach ($results as $result) {
            $score -= $penalties[$result->disease?->severity ?? 'ringan'] ?? 4;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  Collection<int, FishScan>  $scans
     * @return array<int, array{date: string, scans: int, diseases: array<int, string>}>
     */
    private function trend($scans, int $days): array
    {
        $start = now()->startOfDay()->subDays($days - 1);
        $grouped = $scans->groupBy(fn (FishScan $scan) => $scan->created_at->toDateString());

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $dayScans = $grouped->get($date);

            $trend[] = [
                'date' => $date,
                'scans' => $dayScans ? $dayScans->count() : 0,
                'diseases' => $dayScans
                    ? $dayScans->pluck('result.disease.name')->filter()->unique()->values()->all()
                    : [],
            ];
        }

        return $trend;
    }

    /**
     * FR-04.2.3 — penyakit sama muncul ≥2 kali dalam 7 hari.
     *
     * @return array{disease_id: int, name: string, occurrences: int}|null
     */
    private function repeatDiseaseAlert(UserPond $pond): ?array
    {
        $results = ScanResult::query()
            ->whereIn('scan_id', fn ($q) => $q
                ->select('id')
                ->from('fish_scans')
                ->where('pond_id', $pond->id)
                ->where('status', 'done')
                ->where('created_at', '>=', now()->subDays(7)))
            ->whereNotNull('disease_id')
            ->with('disease:id,name')
            ->get();

        $counts = $results->countBy('disease_id')->sortDesc();

        if ($counts->isEmpty() || $counts->first() < 2) {
            return null;
        }

        $diseaseId = $counts->keys()->first();
        $disease = $results->firstWhere('disease_id', $diseaseId)?->disease;

        if (! $disease) {
            return null;
        }

        return [
            'disease_id' => $disease->id,
            'name' => $disease->name,
            'occurrences' => (int) $counts->first(),
        ];
    }

    private function lastScan(UserPond $pond): ?array
    {
        $scan = FishScan::where('pond_id', $pond->id)->latest()->first(['id', 'created_at']);

        if (! $scan) {
            return null;
        }

        return [
            'scan_id' => $scan->id,
            'created_at' => $scan->created_at?->toIso8601String(),
        ];
    }
}
