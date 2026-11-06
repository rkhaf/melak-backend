<?php

namespace App\Console\Commands;

use App\Models\Disease;
use Illuminate\Console\Command;

/**
 * FR-10.11 / API.md §10.3 — label ML service harus sinkron dengan tabel diseases.
 *
 *   php artisan ml:sync-label-map           isi kolom disease_id di label_map.json
 *   php artisan ml:sync-label-map --check   hanya periksa; exit 1 bila belum sinkron (CI)
 *
 * label_map.json adalah sumber urutan indeks model (0..10); seeder tidak boleh
 * mengubah urutan itu, hanya mengisi id-nya.
 */
class SyncLabelMap extends Command
{
    protected $signature = 'ml:sync-label-map
        {--check : Hanya periksa tanpa menulis file, exit 1 bila belum sinkron}';

    protected $description = 'Sinkronkan disease_id di ml-service/label_map.json dengan tabel diseases (FR-10.11)';

    public function handle(): int
    {
        $path = (string) config('ngingu.ml.label_map');

        if (! is_file($path)) {
            $this->error("Label map tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['labels']) || ! is_array($data['labels'])) {
            $this->error("Format label map tidak valid (butuh objek berisi array \"labels\"): {$path}");

            return self::FAILURE;
        }

        $diseases = Disease::query()->get(['id', 'slug', 'name', 'severity'])->keyBy('slug');

        $missing = [];
        $drifted = [];
        $dirty = false;

        foreach ($data['labels'] as $index => $label) {
            $slug = (string) ($label['slug'] ?? '');
            $disease = $diseases->get($slug);

            if (! $disease) {
                $missing[] = $slug !== '' ? $slug : "index #{$index}";

                continue;
            }

            $current = array_key_exists('disease_id', $label) ? $label['disease_id'] : null;

            if ($current === null || (int) $current !== (int) $disease->id) {
                $dirty = true;
            }

            $data['labels'][$index]['disease_id'] = (int) $disease->id;

            // Nama & severity harus sama supaya tampilan ML service dan API
            // Laravel tidak berbeda di mata pengguna.
            if (($label['name'] ?? null) !== $disease->name
                || ($label['severity'] ?? null) !== $disease->severity) {
                $drifted[] = $slug;
            }
        }

        if ($missing !== []) {
            $this->error('Slug berikut tidak ada di tabel diseases — jalankan `php artisan db:seed --class=DiseaseSeeder`:');
            $this->line('  - '.implode("\n  - ", $missing));

            return self::FAILURE;
        }

        if ($drifted !== []) {
            $this->warn('name/severity di label_map berbeda dengan tabel diseases:');
            $this->line('  - '.implode("\n  - ", $drifted));
        }

        if ($this->option('check')) {
            if ($dirty || $drifted !== []) {
                $this->error('Belum sinkron. Jalankan `php artisan ml:sync-label-map`.');

                return self::FAILURE;
            }

            $this->info(sprintf('%d label sinkron dengan tabel diseases.', count($data['labels'])));

            return self::SUCCESS;
        }

        if (! $dirty) {
            $this->info(sprintf('Sudah sinkron, tidak ada yang ditulis (%s).', $path));

            return $drifted !== [] ? self::FAILURE : self::SUCCESS;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false || file_put_contents($path, $json."\n") === false) {
            $this->error("Gagal menulis label map: {$path} (cek permission).");

            return self::FAILURE;
        }

        $this->info(sprintf('%d disease_id ditulis ke %s.', count($data['labels']), $path));

        return $drifted !== [] ? self::FAILURE : self::SUCCESS;
    }
}
