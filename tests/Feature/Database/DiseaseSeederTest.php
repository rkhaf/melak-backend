<?php

namespace Tests\Feature\Database;

use App\Models\Disease;
use App\Models\DiseaseFishType;
use App\Models\DiseasePrevention;
use App\Models\DiseaseTreatment;
use Database\Seeders\DiseaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-10.11 — 11 kelas output model ViT harus ter-seed ke tabel `diseases`
 * dan tetap sinkron dengan ml-service/label_map.json.
 */
class DiseaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Urutan array = indeks kelas model (0..10), jangan diubah sembarangan. */
    private const EXPECTED = [
        'bacterial-red-disease' => ['berat', 'bacteria'],
        'aeromoniasis' => ['berat', 'bacteria'],
        'bacterial-gill-disease' => ['sedang', 'bacteria'],
        'saprolegniasis' => ['sedang', 'fungi'],
        'ikan-sehat' => ['ringan', 'other'],
        'penyakit-parasit' => ['sedang', 'parasite'],
        'white-tail-disease' => ['berat', 'virus'],
        'shrimp-black-gill' => ['sedang', 'bacteria'],
        'udang-sehat' => ['ringan', 'other'],
        'shrimp-white-spot-wssv' => ['berat', 'virus'],
        'shrimp-wssv-black-gill' => ['kritis', 'virus'],
    ];

    public function test_seeds_eleven_diseases_in_model_index_order(): void
    {
        $this->seed(DiseaseSeeder::class);

        $this->assertSame(11, Disease::count());
        $this->assertSame(
            array_keys(self::EXPECTED),
            Disease::query()->orderBy('id')->pluck('slug')->all(),
            'Urutan id diseases harus mengikuti indeks kelas model (0..10).',
        );

        foreach (self::EXPECTED as $slug => [$severity, $category]) {
            $disease = Disease::query()->where('slug', $slug)->firstOrFail();

            $this->assertSame($severity, $disease->severity);
            $this->assertSame($category, $disease->category);
            $this->assertTrue($disease->is_published);
            $this->assertNotSame('', trim((string) $disease->description));
            $this->assertNotSame('', trim((string) $disease->causes));
            $this->assertNotEmpty($disease->symptoms);
            $this->assertNotEmpty($disease->fishTypes);
            $this->assertNotEmpty($disease->preventions);
        }
    }

    public function test_seeding_twice_does_not_duplicate_rows(): void
    {
        $this->seed(DiseaseSeeder::class);

        $before = $this->counts();

        $this->seed(DiseaseSeeder::class);

        $this->assertSame($before, $this->counts());
        $this->assertSame([11, 41, 27, 33], $before);
    }

    public function test_seeder_preserves_ids_when_disease_already_exists(): void
    {
        $this->seed(DiseaseSeeder::class);
        $idBefore = Disease::query()->where('slug', 'aeromoniasis')->value('id');

        $this->seed(DiseaseSeeder::class);

        $this->assertSame(
            $idBefore,
            Disease::query()->where('slug', 'aeromoniasis')->value('id'),
            'id tidak boleh berubah — sudah dirujuk label_map.json dan scan_results.',
        );
    }

    public function test_label_map_is_in_sync_with_seeded_diseases(): void
    {
        $path = realpath(base_path('../ml-service/label_map.json'));

        if ($path === false) {
            $this->markTestSkipped('label_map.json tidak ditemukan di luar monorepo.');
        }

        $this->seed(DiseaseSeeder::class);

        $map = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($map['labels'] ?? null, 'label_map.json harus berisi array "labels".');
        $this->assertCount(11, $map['labels']);

        foreach ($map['labels'] as $index => $label) {
            $disease = Disease::query()->where('slug', $label['slug'] ?? '')->first();

            $this->assertNotNull($disease, "Slug \"{$label['slug']}\" tidak ada di tabel diseases.");
            $this->assertSame($disease->name, $label['name'], "name drift pada index #{$index}.");
            $this->assertSame($disease->severity, $label['severity'], "severity drift pada index #{$index}.");
        }
    }

    public function test_label_map_order_matches_model_class_order(): void
    {
        $labelMap = realpath(base_path('../ml-service/label_map.json'));
        $modelConfig = realpath(base_path('../ml-service/models/config.json'));

        if ($labelMap === false || $modelConfig === false) {
            $this->markTestSkipped('label_map.json / models/config.json tidak tersedia.');
        }

        $labels = json_decode((string) file_get_contents($labelMap), true)['labels'];
        $id2label = json_decode((string) file_get_contents($modelConfig), true)['id2label'];

        // Key id2label berupa string "0".."10"; ksort agar urutannya indeks, bukan
        // urutan kemunculan di file JSON.
        ksort($id2label, SORT_NUMERIC);

        $this->assertSame(
            array_values($id2label),
            array_column($labels, 'model_label'),
            'Urutan label_map.json harus identik dengan id2label checkpoint (FR-10.11).',
        );
    }

    public function test_seeded_disease_is_resolvable_through_api_detail(): void
    {
        $this->seed(DiseaseSeeder::class);

        $disease = Disease::query()->where('slug', 'aeromoniasis')->firstOrFail();

        $this->getJson("/api/diseases/{$disease->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'aeromoniasis')
            ->assertJsonPath('data.severity', 'berat')
            ->assertJsonCount(3, 'data.treatments')
            ->assertJsonCount(3, 'data.preventions')
            ->assertJsonCount(5, 'data.symptoms');
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    private function counts(): array
    {
        return [
            Disease::count(),
            DiseaseFishType::count(),
            DiseaseTreatment::count(),
            DiseasePrevention::count(),
        ];
    }
}
