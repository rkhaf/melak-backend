<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\KecamatanResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Peta sebaran penyakit (API.md §7.11 heatmap, §7.13 pins, FR-07.3).
 */
class MapController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/map/pins (publik) — GIS: setiap scan individu, ditempatkan di
     * koordinat GPS saat scan; fallback ke koordinat kebun bila scan tanpa GPS.
     *
     * Filter opsional: `days` (default 30), `disease_id`.
     *
     * Privasi (FR-07.3): balasan TIDAK memuat nama kebun, label lokasi, atau
     * identitas pemilik/pemindai — hanya jenis penyakit + hasil prediksi.
     * Koordinat dikirim presisi 6 desimal sesuai keputusan produk.
     *
     * Response item:
     *  { id, lat, lng, kecamatan, disease_id, disease_name, severity,
     *    confidence_score, plant_type, scanned_at }
     */
    public function pins(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days'       => 'sometimes|integer|min:1|max:365',
            'disease_id' => 'sometimes|integer|exists:diseases,id',
        ]);

        $days = (int) ($data['days'] ?? 30);
        $resolver = app(KecamatanResolver::class);

        $pins = DB::table('plant_scans')
            ->leftJoin('user_gardens', 'user_gardens.id', '=', 'plant_scans.garden_id')
            ->join('scan_results', 'scan_results.scan_id', '=', 'plant_scans.id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->where('plant_scans.status', 'done')
            ->where('plant_scans.created_at', '>=', now()->subDays($days))
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('plant_scans.location_lat')
                        ->whereNotNull('plant_scans.location_lng');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('user_gardens.location_lat')
                        ->whereNotNull('user_gardens.location_lng');
                });
            })
            ->when(! empty($data['disease_id']), fn ($q) => $q->where('scan_results.disease_id', $data['disease_id']))
            ->orderByDesc('plant_scans.created_at')
            ->get([
                'plant_scans.id          as id',
                DB::raw('CASE WHEN plant_scans.location_lat IS NOT NULL AND plant_scans.location_lng IS NOT NULL THEN plant_scans.location_lat ELSE user_gardens.location_lat END as lat'),
                DB::raw('CASE WHEN plant_scans.location_lat IS NOT NULL AND plant_scans.location_lng IS NOT NULL THEN plant_scans.location_lng ELSE user_gardens.location_lng END as lng'),
                'plant_scans.kecamatan   as kecamatan',
                'scan_results.disease_id   as disease_id',
                'diseases.name            as disease_name',
                'diseases.severity        as severity',
                'scan_results.confidence_score as confidence_score',
                'plant_scans.plant_type     as plant_type',
                'plant_scans.created_at    as scanned_at',
            ])
            ->map(function ($row) use ($resolver) {
                $lat = round((float) $row->lat, 6);
                $lng = round((float) $row->lng, 6);

                return [
                    'id'               => $row->id,
                    'lat'              => $lat,
                    'lng'              => $lng,
                    'kecamatan'        => $row->kecamatan ?? $resolver->resolve($lat, $lng),
                    'disease_id'       => (int) $row->disease_id,
                    'disease_name'     => $row->disease_name,
                    'severity'         => $row->severity,
                    'confidence_score' => round((float) $row->confidence_score, 1),
                    'plant_type'        => $row->plant_type,
                    'scanned_at'       => $row->scanned_at,
                ];
            })
            ->values();

        return $this->ok($pins);
    }

    /**
     * GET /api/map/heatmap (publik) — akumulasi kasus per kecamatan
     * (fokus Kab. Indramayu, FR-07.3).
     *
     * Tiap scan selesai = 1 kasus pada kecamatan hasil pemetaan koordinatnya
     * (GPS scan, fallback koordinat kebun). Grup yang sama sekali tidak punya
     * koordinat tidak dikirim karena marker-nya tidak bisa digambar.
     *
     * Response item: { kecamatan, lat, lng, cases, top_disease }
     * `lat`/`lng` = titik tengah rata-rata seluruh scan di kecamatan tsb.
     */
    public function heatmap(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
            'province' => 'sometimes|string|max:80',
            'disease_id' => 'sometimes|integer|exists:diseases,id',
        ]);

        $days = (int) ($data['days'] ?? 30);
        $resolver = app(KecamatanResolver::class);

        $rows = DB::table('plant_scans')
            ->join('users', 'users.id', '=', 'plant_scans.user_id')
            ->join('scan_results', 'scan_results.scan_id', '=', 'plant_scans.id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->leftJoin('user_gardens', 'user_gardens.id', '=', 'plant_scans.garden_id')
            ->where('plant_scans.status', 'done')
            ->where('plant_scans.created_at', '>=', now()->subDays($days))
            ->when(! empty($data['province']), fn ($q) => $q->where('users.province', $data['province']))
            ->when(! empty($data['disease_id']), fn ($q) => $q->where('scan_results.disease_id', $data['disease_id']))
            ->get([
                'plant_scans.kecamatan as kecamatan',
                'diseases.id as disease_id',
                'diseases.name as disease_name',
                DB::raw('COALESCE(plant_scans.location_lat, user_gardens.location_lat) as lat'),
                DB::raw('COALESCE(plant_scans.location_lng, user_gardens.location_lng) as lng'),
            ]);

        $map = [];

        foreach ($rows as $row) {
            $lat = $row->lat !== null ? round((float) $row->lat, 6) : null;
            $lng = $row->lng !== null ? round((float) $row->lng, 6) : null;

            $kecamatan = $row->kecamatan
                ?? $resolver->resolve($lat, $lng)
                ?? 'Lainnya';

            $map[$kecamatan] ??= [
                'kecamatan' => $kecamatan,
                'cases' => 0,
                'lats' => [],
                'lngs' => [],
                'diseases' => [],
            ];
            $map[$kecamatan]['cases']++;

            if ($lat !== null && $lng !== null) {
                $map[$kecamatan]['lats'][] = $lat;
                $map[$kecamatan]['lngs'][] = $lng;
            }

            $map[$kecamatan]['diseases'][$row->disease_id] = [
                'name' => $row->disease_name,
                'count' => ($map[$kecamatan]['diseases'][$row->disease_id]['count'] ?? 0) + 1,
            ];
        }

        $heatmap = collect($map)
            ->filter(fn (array $entry) => $entry['lats'] !== [])
            ->map(function (array $entry) {
                $top = collect($entry['diseases'])->sortByDesc('count')->first();

                return [
                    'kecamatan' => $entry['kecamatan'],
                    'lat' => round(array_sum($entry['lats']) / count($entry['lats']), 4),
                    'lng' => round(array_sum($entry['lngs']) / count($entry['lngs']), 4),
                    'cases' => $entry['cases'],
                    'top_disease' => $top['name'] ?? null,
                ];
            })
            ->sortByDesc('cases')
            ->values();

        return $this->ok($heatmap);
    }
}
