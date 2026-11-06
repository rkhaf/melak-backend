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
<<<<<<< HEAD
     * GET /api/map/pins (publik) — GIS: setiap scan individu, ditempatkan di
     * koordinat GPS saat scan; fallback ke koordinat kolam bila scan tanpa GPS.
=======
     * GET /api/map/pins (publik) — GIS: setiap scan individu yang memiliki
     * koordinat, digunakan mobile untuk menandai lokasi tepat di peta.
     * Koordinat yang dipakai adalah GPS saat scan dilakukan; koordinat
     * tambak menjadi cadangan bila scan tidak membawa GPS.
>>>>>>> 0202caedcfeea7f3a7a9d5e065d907579030a5e9
     *
     * Filter opsional: `days` (default 30), `disease_id`.
     *
     * Privasi (FR-07.3): balasan TIDAK memuat nama kolam, label lokasi, atau
     * identitas pemilik/pemindai — hanya jenis penyakit + hasil prediksi.
     * Koordinat dikirim presisi 6 desimal sesuai keputusan produk.
     *
     * Response item:
     *  { id, lat, lng, kecamatan, disease_id, disease_name, severity,
     *    confidence_score, fish_type, scanned_at }
     */
    public function pins(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days'       => 'sometimes|integer|min:1|max:365',
            'disease_id' => 'sometimes|integer|exists:diseases,id',
        ]);

        $days = (int) ($data['days'] ?? 30);
        $resolver = app(KecamatanResolver::class);

<<<<<<< HEAD
        $pins = DB::table('fish_scans')
=======
        $pins = \Illuminate\Support\Facades\DB::table('fish_scans')
>>>>>>> 0202caedcfeea7f3a7a9d5e065d907579030a5e9
            ->leftJoin('user_ponds', 'user_ponds.id', '=', 'fish_scans.pond_id')
            ->join('scan_results', 'scan_results.scan_id', '=', 'fish_scans.id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->where('fish_scans.status', 'done')
            ->where('fish_scans.created_at', '>=', now()->subDays($days))
<<<<<<< HEAD
            ->whereRaw('COALESCE(fish_scans.location_lat, user_ponds.location_lat) IS NOT NULL')
            ->whereRaw('COALESCE(fish_scans.location_lng, user_ponds.location_lng) IS NOT NULL')
=======
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('fish_scans.location_lat')
                        ->whereNotNull('fish_scans.location_lng');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('user_ponds.location_lat')
                        ->whereNotNull('user_ponds.location_lng');
                });
            })
>>>>>>> 0202caedcfeea7f3a7a9d5e065d907579030a5e9
            ->when(! empty($data['disease_id']), fn ($q) => $q->where('scan_results.disease_id', $data['disease_id']))
            ->orderByDesc('fish_scans.created_at')
            ->get([
                'fish_scans.id          as id',
<<<<<<< HEAD
                DB::raw('COALESCE(fish_scans.location_lat, user_ponds.location_lat) as lat'),
                DB::raw('COALESCE(fish_scans.location_lng, user_ponds.location_lng) as lng'),
                'fish_scans.kecamatan   as kecamatan',
=======
                'fish_scans.location_lat  as scan_lat',
                'fish_scans.location_lng  as scan_lng',
                'user_ponds.location_lat  as pond_lat',
                'user_ponds.location_lng  as pond_lng',
                'user_ponds.name          as pond_name',
                'user_ponds.location_label as location_label',
>>>>>>> 0202caedcfeea7f3a7a9d5e065d907579030a5e9
                'scan_results.disease_id   as disease_id',
                'diseases.name            as disease_name',
                'diseases.severity        as severity',
                'scan_results.confidence_score as confidence_score',
                'fish_scans.fish_type     as fish_type',
                'fish_scans.created_at    as scanned_at',
            ])
<<<<<<< HEAD
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
                    'fish_type'        => $row->fish_type,
                    'scanned_at'       => $row->scanned_at,
                ];
            })
=======
            ->map(fn ($row) => [
                'id'               => $row->id,
                // Utamakan GPS saat scan; lokasi kolam jadi cadangan.
                'lat'              => round((float) ($row->scan_lat ?? $row->pond_lat), 6),
                'lng'              => round((float) ($row->scan_lng ?? $row->pond_lng), 6),
                'pond_name'        => $row->pond_name,
                'location_label'   => $row->location_label,
                'disease_id'       => (int) $row->disease_id,
                'disease_name'     => $row->disease_name,
                'severity'         => $row->severity,
                'confidence_score' => round((float) $row->confidence_score, 1),
                'fish_type'        => $row->fish_type,
                'scanned_at'       => $row->scanned_at,
            ])
>>>>>>> 0202caedcfeea7f3a7a9d5e065d907579030a5e9
            ->values();

        return $this->ok($pins);
    }

    /**
     * GET /api/map/heatmap (publik) — akumulasi kasus per kecamatan
     * (fokus Kab. Indramayu, FR-07.3).
     *
     * Tiap scan selesai = 1 kasus pada kecamatan hasil pemetaan koordinatnya
     * (GPS scan, fallback koordinat kolam). Grup yang sama sekali tidak punya
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

        $rows = DB::table('fish_scans')
            ->join('users', 'users.id', '=', 'fish_scans.user_id')
            ->join('scan_results', 'scan_results.scan_id', '=', 'fish_scans.id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->leftJoin('user_ponds', 'user_ponds.id', '=', 'fish_scans.pond_id')
            ->where('fish_scans.status', 'done')
            ->where('fish_scans.created_at', '>=', now()->subDays($days))
            ->when(! empty($data['province']), fn ($q) => $q->where('users.province', $data['province']))
            ->when(! empty($data['disease_id']), fn ($q) => $q->where('scan_results.disease_id', $data['disease_id']))
            ->get([
                'fish_scans.kecamatan as kecamatan',
                'diseases.id as disease_id',
                'diseases.name as disease_name',
                DB::raw('COALESCE(fish_scans.location_lat, user_ponds.location_lat) as lat'),
                DB::raw('COALESCE(fish_scans.location_lng, user_ponds.location_lng) as lng'),
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
