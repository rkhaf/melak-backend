<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\UserGarden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kebun / lahan (API.md §7.2 — §7.6, FR-08.1 — FR-08.2).
 */
class GardenController extends Controller
{
    use ApiResponse, ScanPresenter;

    /**
     * GET /api/gardens (API.md §7.2).
     */
    public function index(Request $request): JsonResponse
    {
        $gardens = UserGarden::where('user_id', $request->user()->id)
            ->withCount(['scans as scans_count'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($gardens->map(fn (UserGarden $garden) => $this->gardenPayload($garden))->values());
    }

    /**
     * GET /api/gardens/{id} (API.md §7.14) — detail satu lahan milik sendiri.
     * Dipakai form "Ubah Lahan" & layar laporan kesehatan untuk prefill
     * data segar (bukan dari argumen route).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $garden = $this->ownGarden($request, $id);

        if (! $garden) {
            return $this->fail('Kebun tidak ditemukan.', 404);
        }

        return $this->ok($this->gardenPayload($garden));
    }

    /**
     * POST /api/gardens (API.md §7.3).
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
            'plant_types' => 'sometimes|array|max:10',
            'plant_types.*' => 'string|max:30',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama kebun wajib diisi.',
            'name.min' => 'Nama kebun minimal 2 karakter.',
            'plant_types.max' => 'Maksimal 10 jenis tanaman.',
        ]);

        $garden = UserGarden::create([
            'user_id' => $request->user()->id,
            'health_score' => 100,
            ...$data,
        ]);

        return $this->created($this->gardenPayload($garden), 'Kebun berhasil ditambahkan');
    }

    /**
     * PUT /api/gardens/{id} (API.md §7.4).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $garden = $this->ownGarden($request, $id);

        if (! $garden) {
            return $this->fail('Kebun tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|min:2|max:80',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'area_m2' => 'nullable|numeric|min:1|max:1000000',
            'capacity' => 'nullable|integer|min:1|max:10000000',
            'plant_types' => 'sometimes|array|max:10',
            'plant_types.*' => 'string|max:30',
            'description' => 'nullable|string|max:500',
        ]);

        $garden->fill($data);
        $garden->save();

        return $this->ok($this->gardenPayload($garden), 'Kebun berhasil diperbarui');
    }

    /**
     * DELETE /api/gardens/{id} (API.md §7.5).
     */
    public function destroy(Request $request, string $id): Response
    {
        $garden = $this->ownGarden($request, $id);

        if (! $garden) {
            return $this->fail('Kebun tidak ditemukan.', 404);
        }

        $garden->delete();

        return $this->noContent();
    }

    /**
     * GET /api/gardens/{id}/health-report (API.md §7.6, FR-04.2).
     */
    public function healthReport(Request $request, string $id): JsonResponse
    {
        $garden = $this->ownGarden($request, $id);

        if (! $garden) {
            return $this->fail('Kebun tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'period' => 'sometimes|in:7d,30d,90d',
        ]);

        $days = match ($data['period'] ?? '7d') {
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $scans = PlantScan::with(['result.disease'])
            ->where('garden_id', $garden->id)
            ->where('status', 'done')
            ->where('created_at', '>=', now()->startOfDay()->subDays($days - 1))
            ->get();

        $garden->health_score = $this->computeHealthScore($garden);
        $garden->save();

        return $this->ok([
            'garden' => ['id' => $garden->id, 'name' => $garden->name],
            'health_score' => $garden->health_score,
            'trend' => $this->trend($scans, $days),
            'repeat_disease_alert' => $this->repeatDiseaseAlert($garden),
            'last_scan' => $this->lastScan($garden),
        ]);
    }

    private function ownGarden(Request $request, string $id): ?UserGarden
    {
        return UserGarden::where('user_id', $request->user()->id)->find($id);
    }

    private function gardenPayload(UserGarden $garden): array
    {
        return [
            'id' => $garden->id,
            'name' => $garden->name,
            'description' => $garden->description,
            'location_lat' => $garden->location_lat !== null ? (float) $garden->location_lat : null,
            'location_lng' => $garden->location_lng !== null ? (float) $garden->location_lng : null,
            'location_label' => $garden->location_label,
            'area_m2' => $garden->area_m2 !== null ? (float) $garden->area_m2 : null,
            'capacity' => $garden->capacity !== null ? (int) $garden->capacity : null,
            'plant_types' => $garden->plant_types ?? [],
            'health_score' => (int) $garden->health_score,
            'scans_count' => (int) ($garden->scans_count ?? $garden->scans()->count()),
            'created_at' => $garden->created_at?->toIso8601String(),
        ];
    }

    /**
     * FR-04.2.2 — skor 0-100 dari frekuensi & keparahan penyakit (30 hari terakhir).
     */
    private function computeHealthScore(UserGarden $garden): int
    {
        $penalties = ['ringan' => 4, 'sedang' => 8, 'berat' => 15, 'kritis' => 25];

        $results = ScanResult::query()
            ->whereIn('scan_id', fn ($q) => $q
                ->select('id')
                ->from('plant_scans')
                ->where('garden_id', $garden->id)
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
     * @param  Collection<int, PlantScan>  $scans
     * @return array<int, array{date: string, scans: int, diseases: array<int, string>}>
     */
    private function trend($scans, int $days): array
    {
        $start = now()->startOfDay()->subDays($days - 1);
        $grouped = $scans->groupBy(fn (PlantScan $scan) => $scan->created_at->toDateString());

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
    private function repeatDiseaseAlert(UserGarden $garden): ?array
    {
        $results = ScanResult::query()
            ->whereIn('scan_id', fn ($q) => $q
                ->select('id')
                ->from('plant_scans')
                ->where('garden_id', $garden->id)
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

    private function lastScan(UserGarden $garden): ?array
    {
        $scan = PlantScan::where('garden_id', $garden->id)->latest()->first(['id', 'created_at']);

        if (! $scan) {
            return null;
        }

        return [
            'scan_id' => $scan->id,
            'created_at' => $scan->created_at?->toIso8601String(),
        ];
    }
}
