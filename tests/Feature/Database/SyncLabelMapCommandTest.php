<?php

namespace Tests\Feature\Database;

use App\Models\Disease;
use Database\Seeders\DiseaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-10.11 / API.md §10.3 — kolom disease_id di label_map.json harus sinkron
 * dengan tabel `diseases`.
 *
 * File sementara dipakai agar test tidak pernah menulis ke label_map.json asli
 * di monorepo.
 */
class SyncLabelMapCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'label_map_'.uniqid().'.json';
        config(['ngingu.ml.label_map' => $this->path]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_writes_disease_ids_into_label_map(): void
    {
        $this->seed(DiseaseSeeder::class);
        $this->writeLabelMap($this->labelsWith(null));

        $this->artisan('ml:sync-label-map')
            ->expectsOutputToContain('ditulis ke')
            ->assertSuccessful();

        $synced = $this->readLabelMap();

        foreach ($synced as $index => $label) {
            $expected = Disease::query()->where('slug', $label['slug'])->value('id');

            $this->assertSame($expected, $label['disease_id'], "disease_id index #{$index} belum sinkron.");
        }

        $this->assertSame(1, $synced[0]['disease_id']);
        $this->assertSame(11, $synced[10]['disease_id']);
    }

    public function test_check_passes_when_already_synced(): void
    {
        $this->seed(DiseaseSeeder::class);

        $labels = $this->labelsWith(null);

        foreach ($labels as $index => $label) {
            $labels[$index]['disease_id'] = Disease::query()->where('slug', $label['slug'])->value('id');
        }

        $this->writeLabelMap($labels);

        $this->artisan('ml:sync-label-map --check')
            ->expectsOutputToContain('sinkron')
            ->assertSuccessful();
    }

    public function test_check_fails_when_disease_id_is_null_or_wrong(): void
    {
        $this->seed(DiseaseSeeder::class);
        $this->writeLabelMap($this->labelsWith(null));

        $this->artisan('ml:sync-label-map --check')
            ->expectsOutputToContain('Belum sinkron')
            ->assertFailed();

        $labels = $this->labelsWith(null);
        $labels[3]['disease_id'] = 999;
        $this->writeLabelMap($labels);

        $this->artisan('ml:sync-label-map --check')->assertFailed();
    }

    public function test_check_fails_when_name_or_severity_drifts_from_database(): void
    {
        $this->seed(DiseaseSeeder::class);

        $labels = $this->labelsWith(null);

        foreach ($labels as $index => $label) {
            $labels[$index]['disease_id'] = Disease::query()->where('slug', $label['slug'])->value('id');
        }

        $labels[0]['severity'] = 'kritis';
        $this->writeLabelMap($labels);

        $this->artisan('ml:sync-label-map --check')
            ->expectsOutputToContain('berbeda dengan tabel diseases')
            ->assertFailed();
    }

    public function test_fails_when_label_map_contains_unknown_slug(): void
    {
        $this->seed(DiseaseSeeder::class);

        $labels = $this->labelsWith(null);
        $labels[2]['slug'] = 'slug-tidak-ada';

        $this->writeLabelMap($labels);

        $this->artisan('ml:sync-label-map')
            ->expectsOutputToContain('tidak ada di tabel diseases')
            ->assertFailed();
    }

    public function test_fails_when_label_map_file_is_missing(): void
    {
        $this->seed(DiseaseSeeder::class);
        config(['ngingu.ml.label_map' => $this->path.'.tidak-ada']);

        $this->artisan('ml:sync-label-map')
            ->expectsOutputToContain('tidak ditemukan')
            ->assertFailed();
    }

    /**
     * Menyalin isi label_map.json asli (urutan indeks model) ke file sementara.
     *
     * @return array<int, array<string, mixed>>
     */
    private function labelsWith(?int $diseaseId): array
    {
        $path = realpath(base_path('../ml-service/label_map.json'));

        if ($path === false) {
            $this->markTestSkipped('label_map.json asli tidak ditemukan di monorepo.');
        }

        $labels = json_decode((string) file_get_contents($path), true)['labels'];

        foreach ($labels as $index => $label) {
            $labels[$index]['disease_id'] = $diseaseId;
        }

        return $labels;
    }

    /**
     * @param  array<int, array<string, mixed>>  $labels
     */
    private function writeLabelMap(array $labels): void
    {
        file_put_contents(
            $this->path,
            json_encode(['model_version' => 'vit-v1.0.0', 'labels' => $labels], JSON_PRETTY_PRINT),
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function readLabelMap(): array
    {
        return json_decode((string) file_get_contents($this->path), true)['labels'];
    }
}
