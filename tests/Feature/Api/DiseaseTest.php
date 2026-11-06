<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\DiseasePlantType;
use App\Models\DiseaseImage;
use App\Models\DiseasePrevention;
use App\Models\DiseaseTreatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiseaseTest extends TestCase
{
    use RefreshDatabase;

    private function fullDisease(): Disease
    {
        $disease = Disease::factory()->create([
            'name' => 'Bercak Bakteri pada Tomat',
            'name_en' => 'Tomato Bacterial Spot',
            'slug' => 'tomato-bacterial-spot',
            'category' => 'bacteria',
            'severity' => 'berat',
            'description' => 'Penyakit bakteri yang menyebabkan bercak pada daun tomat.',
            'causes' => 'Bakteri Xanthomonas.',
            'symptoms' => "Bercak gelap pada daun\nDaun menguning\nBercak kasar pada buah",
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
        ]);

        DiseasePlantType::create(['disease_id' => $disease->id, 'plant_type' => 'Tomat']);
        DiseasePlantType::create(['disease_id' => $disease->id, 'plant_type' => 'Jagung']);
        DiseaseImage::create(['disease_id' => $disease->id, 's3_url' => 'https://cdn.melak.id/diseases/tomato-spot.webp', 'caption' => 'Bercak daun tomat', 'is_primary' => true, 'order_index' => 0]);
        DiseaseTreatment::create(['disease_id' => $disease->id, 'step_order' => 1, 'title' => 'Buang bagian yang terinfeksi', 'description' => 'Pangkas daun dan buah bergejala.', 'medicines' => [], 'duration_days' => 1]);
        DiseaseTreatment::create(['disease_id' => $disease->id, 'step_order' => 2, 'title' => 'Kurangi kelembapan daun', 'description' => 'Siram di pangkal tanaman.', 'medicines' => [], 'duration_days' => 5]);
        DiseasePrevention::create(['disease_id' => $disease->id, 'step_order' => 1, 'description' => 'Gunakan benih sehat dan bersihkan alat kebun.']);

        return $disease->fresh(['images', 'plantTypes', 'treatments', 'preventions']);
    }

    public function test_list_diseases_with_filters_and_meta(): void
    {
        $this->fullDisease();
        Disease::factory()->create(['category' => 'fungi', 'severity' => 'ringan', 'name' => 'Alternaria', 'slug' => 'alternaria']);
        Disease::factory()->create(['is_published' => false, 'name' => 'Draft', 'slug' => 'draft']);

        $this->getJson('/api/diseases')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Alternaria')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category', 'severity', 'thumbnail_url', 'plant_types', 'recovery_days']]]);

        $this->getJson('/api/diseases?category=bacteria')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'tomato-bacterial-spot')
            ->assertJsonPath('data.0.thumbnail_url', 'https://cdn.melak.id/diseases/tomato-spot.webp');

        $this->getJson('/api/diseases?plant_type=Tomat')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/diseases?severity=kritis')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_disease_detail_returns_full_guides(): void
    {
        $this->fullDisease();

        $response = $this->getJson('/api/diseases/tomato-bacterial-spot')
            ->assertOk()
            ->assertJsonPath('data.name', 'Bercak Bakteri pada Tomat')
            ->assertJsonPath('data.severity', 'berat')
            ->assertJsonPath('data.symptoms.0', 'Bercak gelap pada daun')
            ->assertJsonPath('data.images.0.url', 'https://cdn.melak.id/diseases/tomato-spot.webp')
            ->assertJsonPath('data.treatments.0.title', 'Buang bagian yang terinfeksi')
            ->assertJsonPath('data.treatments.1.medicines', [])
            ->assertJsonPath('data.preventions.0', 'Gunakan benih sehat dan bersihkan alat kebun.')
            ->assertJsonPath('data.recovery_days.min', 5)
            ->assertJsonPath('data.recovery_days.max', 14)
            ->assertJsonPath('data.related_forum_posts_count', 0);

        $this->assertContains('Tomat', $response->json('data.plant_types'));
    }

    public function test_disease_detail_404_for_unknown_slug(): void
    {
        $this->getJson('/api/diseases/tidak-ada')->assertStatus(404)->assertJsonPath('success', false);
    }

    public function test_disease_detail_also_accepts_numeric_id(): void
    {
        $disease = $this->fullDisease();

        $this->getJson('/api/diseases/'.$disease->id)
            ->assertOk()
            ->assertJsonPath('data.id', $disease->id)
            ->assertJsonPath('data.slug', 'tomato-bacterial-spot');
    }

    public function test_search_matches_name_symptoms_and_plant_type(): void
    {
        $this->fullDisease();
        Disease::factory()->create([
            'name' => 'Kapang Daun Tomat',
            'name_en' => 'Tomato Leaf Mold',
            'slug' => 'tomato-leaf-mold',
            'category' => 'fungi',
            'description' => 'Jamur pada daun tomat.',
            'causes' => 'Jamur Passalora fulva.',
            'symptoms' => 'Lapisan beludru pada sisi bawah daun.',
        ]);

        $this->getJson('/api/diseases/search?q=bercak')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'tomato-bacterial-spot')
            ->assertJsonPath('data.0.thumbnail_url', 'https://cdn.melak.id/diseases/tomato-spot.webp')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category', 'severity', 'thumbnail_url', 'snippet']]]);

        $this->getJson('/api/diseases/search?q=tomat')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/diseases/search?q=kapang')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/diseases/search?q=x')
            ->assertStatus(422);
    }

    public function test_similar_diseases_returns_max_four(): void
    {
        $this->fullDisease();
        Disease::factory()->create(['category' => 'bacteria', 'name' => 'Bercak', 'slug' => 'bercak-lain']);
        Disease::factory()->create(['category' => 'fungi', 'name' => 'Kapang', 'slug' => 'kapang']);

        $response = $this->getJson('/api/diseases/tomato-bacterial-spot/similar');

        $response->assertOk()->assertJsonPath('data.0.slug', 'bercak-lain');
        $this->assertLessThanOrEqual(4, count($response->json('data')));
        $this->assertNotContains('tomato-bacterial-spot', array_column($response->json('data'), 'slug'));
    }

    public function test_unpublished_disease_is_not_public(): void
    {
        Disease::factory()->create(['slug' => 'rahasia', 'name' => 'Rahasia', 'is_published' => false]);

        $this->getJson('/api/diseases/rahasia')->assertStatus(404);
        $this->getJson('/api/diseases/rahasia/similar')->assertStatus(404);
    }
}
