<?php

namespace App\Console\Commands;

use App\Models\PlantScan;
use App\Models\UserGarden;
use App\Support\KecamatanResolver;
use Illuminate\Console\Command;

/**
 * Isi kolom plant_scans.kecamatan untuk data lama (peta sebaran per kecamatan).
 *
 * Pemetaan dilakukan saat scan dibuat; command ini menutup scan yang sudah ada
 * sebelum kolom kecamatan tersedia. Titik tanpa koordinat atau di luar polygon
 * Kab. Indramayu dibiarkan null.
 */
class BackfillScanKecamatan extends Command
{
    protected $signature = 'scan:backfill-kecamatan {--dry-run : Hitung tanpa menyimpan perubahan}';

    protected $description = 'Isi plant_scans.kecamatan dari koordinat GPS scan (fallback koordinat kebun)';

    public function handle(KecamatanResolver $resolver): int
    {
        $dry = (bool) $this->option('dry-run');
        $gardens = null;
        $updated = 0;
        $skipped = 0;

        // chunkById mengurutkan sendiri berdasarkan id — jangan tambah orderBy.
        PlantScan::query()
            ->whereNull('kecamatan')
            ->chunkById(200, function ($scans) use ($resolver, $dry, &$updated, &$skipped, &$gardens) {
                foreach ($scans as $scan) {
                    $lat = $scan->location_lat;
                    $lng = $scan->location_lng;

                    if ($lat === null && $lng === null && $scan->garden_id !== null) {
                        $gardens ??= UserGarden::query()->whereIn('id', $scans->pluck('garden_id'))->get()->keyBy('id');
                        $garden = $gardens->get($scan->garden_id);
                        $lat = $garden?->location_lat;
                        $lng = $garden?->location_lng;
                    }

                    $kecamatan = $resolver->resolve(
                        $lat !== null ? (float) $lat : null,
                        $lng !== null ? (float) $lng : null,
                    );

                    if ($kecamatan === null) {
                        $skipped++;
                        continue;
                    }

                    $updated++;

                    if (! $dry) {
                        PlantScan::whereKey($scan->id)->toBase()->update(['kecamatan' => $kecamatan]);
                    }
                }
            });

        $this->info(sprintf(
            '%skecamatan terisi: %d, tidak terpetakan: %d',
            $dry ? '[dry-run] ' : '',
            $updated,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
