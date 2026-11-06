<?php

namespace App\Support;

/**
 * Pemetaan koordinat → nama kecamatan untuk peta sebaran (FR-07.3).
 *
 * Polygon batas 31 kecamatan Kabupaten Indramayu dibundel offline di
 * resources/data/kecamatan_indramayu.geojson, sehingga resolusi lokasi tidak
 * bergantung layanan geocoding luar dan tetap jalan saat ML/API luar mati.
 * Lihat resources/data/README.md untuk sumber & cara regenerasi file.
 *
 * Titik di luar polygon (di luar Indramayu, di laut, atau tepat di batas)
 * menghasilkan null — pemakai mengelompokkannya sebagai "Lainnya".
 */
class KecamatanResolver
{
    /**
     * Area hasil load file, tiap entri: ['name' => string, 'rings' => list<list<[lng, lat]>>].
     *
     * @var list<array{name: string, rings: list<list<array{0: float, 1: float}>>}>|null
     */
    private static ?array $areas = null;

    private static bool $loaded = false;

    private static array $memo = [];

    public function resolve(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $key = round($lat, 5).'|'.round($lng, 5);

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        $result = null;

        foreach (self::areas() as $area) {
            if ($this->insideArea($lng, $lat, $area['rings'])) {
                $result = $area['name'];
                break;
            }
        }

        return self::$memo[$key] = $result;
    }

    /** Nama kecamatan yang tersedia (untuk debug/health check). */
    public function names(): array
    {
        return array_map(fn (array $area) => $area['name'], self::areas());
    }

    /** @return list<array{name: string, rings: list<list<array{0: float, 1: float}>>}> */
    private static function areas(): array
    {
        if (self::$loaded) {
            return self::$areas ?? [];
        }

        self::$loaded = true;
        self::$areas = [];

        $path = resource_path('data/kecamatan_indramayu.geojson');

        if (! is_file($path)) {
            return self::$areas;
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || ($json['type'] ?? null) !== 'FeatureCollection') {
            return self::$areas;
        }

        foreach ($json['features'] ?? [] as $feature) {
            $name = trim((string) ($feature['properties']['kecamatan'] ?? ''));
            $coordinates = $feature['geometry']['coordinates'] ?? [];
            $type = $feature['geometry']['type'] ?? null;

            if ($name === '' || $coordinates === []) {
                continue;
            }

            // Polygon: [ring, ...] — MultiPolygon: [[ring, ...], ...].
            $polygons = $type === 'MultiPolygon' ? $coordinates : [$coordinates];
            $rings = [];

            foreach ($polygons as $polygon) {
                foreach ($polygon as $ring) {
                    $points = array_map(
                        fn ($point) => [(float) $point[0], (float) $point[1]],
                        $ring
                    );

                    if (count($points) >= 4) {
                        $rings[] = $points;
                    }
                }
            }

            if ($rings !== []) {
                self::$areas[] = ['name' => $name, 'rings' => $rings];
            }
        }

        return self::$areas;
    }

    /**
     * Aturan genap-ganjil (ray casting) atas seluruh ring suatu area:
     * titik di dalam poligon luar tapi di dalam lubang dihitung keluar.
     *
     * @param  list<list<array{0: float, 1: float}>>  $rings
     */
    private function insideArea(float $lng, float $lat, array $rings): bool
    {
        $inside = false;

        foreach ($rings as $ring) {
            if ($this->insideRing($lng, $lat, $ring)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $ring
     */
    private function insideRing(float $lng, float $lat, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $lat) !== ($yj > $lat)
                && $lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
