<?php

namespace App\Console\Commands;

use App\Models\FishScan;
use App\Models\UserPond;
use App\Support\KecamatanResolver;
use Illuminate\Console\Command;

/**
 * Isi kolom fish_scans.kecamatan untuk data lama (peta sebaran per kecamatan).
 *
 * Pemetaan dilakukan saat scan dibuat; command ini menutup scan yang sudah ada
 * sebelum kolom kecamatan tersedia. Titik tanpa koordinat atau di luar polygon
 * Kab. Indramayu dibiarkan null.
 */
class BackfillScanKecamatan extends Command
{
    protected $signature = 'scan:backfill-kecamatan {--dry-run : Hitung tanpa menyimpan perubahan}';

    protected $description = 'Isi fish_scans.kecamatan dari koordinat GPS scan (fallback koordinat kolam)';

    public function handle(KecamatanResolver $resolver): int
    {
        $dry = (bool) $this->option('dry-run');
        $ponds = null;
        $updated = 0;
        $skipped = 0;

        // chunkById mengurutkan sendiri berdasarkan id — jangan tambah orderBy.
        FishScan::query()
            ->whereNull('kecamatan')
            ->chunkById(200, function ($scans) use ($resolver, $dry, &$updated, &$skipped, &$ponds) {
                foreach ($scans as $scan) {
                    $lat = $scan->location_lat;
                    $lng = $scan->location_lng;

                    if ($lat === null && $lng === null && $scan->pond_id !== null) {
                        $ponds ??= UserPond::query()->whereIn('id', $scans->pluck('pond_id'))->get()->keyBy('id');
                        $pond = $ponds->get($scan->pond_id);
                        $lat = $pond?->location_lat;
                        $lng = $pond?->location_lng;
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
                        FishScan::whereKey($scan->id)->toBase()->update(['kecamatan' => $kecamatan]);
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
