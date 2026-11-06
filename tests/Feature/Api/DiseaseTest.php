<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\DiseaseFishType;
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
            'name' => 'White Spot (Ich)',
            'name_en' => 'White Spot',
            'slug' => 'white-spot-ich',
            'category' => 'parasite',
            'severity' => 'berat',
            'description' => 'Penyakit parasit yang menyebabkan bintik putih pada tubuh ikan.',
            'causes' => 'Parasit Ichthyophthirius multifiliis.',
            'symptoms' => "Bintik putih seperti garam di kulit & sirip\nIkan menggosokkan badan ke dinding kolam\nNafsu makan menurun",
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
        ]);

        DiseaseFishType::create(['disease_id' => $disease->id, 'fish_type' => 'Lele']);
        DiseaseFishType::create(['disease_id' => $disease->id, 'fish_type' => 'Nila']);
        DiseaseImage::create(['disease_id' => $disease->id, 's3_url' => 'https://cdn.melak.id/diseases/ich-1.webp', 'caption' => 'Bintik putih', 'is_primary' => true, 'order_index' => 0]);
        DiseaseTreatment::create(['disease_id' => $disease->id, 'step_order' => 1, 'title' => 'Pisahkan ikan yang terinfeksi', 'description' => 'Pindahkan ke kolam karantina.', 'medicines' => [], 'duration_days' => 1]);
        DiseaseTreatment::create(['disease_id' => $disease->id, 'step_order' => 2, 'title' => 'Pemberian garam', 'description' => '3 ppt garam ikan.', 'medicines' => [['name' => 'Garam ikan', 'dose' => '3 gram / liter', 'frequency' => '1x sehari']], 'duration_days' => 5]);
        DiseasePrevention::create(['disease_id' => $disease->id, 'step_order' => 1, 'description' => 'Karantina ikan baru minimal 7 hari']);

        return $disease->fresh(['images', 'fishTypes', 'treatments', 'preventions']);
    }

    public function test_list_diseases_with_filters_and_meta(): void
    {
        $this->fullDisease();
        Disease::factory()->create(['category' => 'bacteria', 'severity' => 'ringan', 'name' => 'Aeromonas', 'slug' => 'aeromonas']);
        Disease::factory()->create(['is_published' => false, 'name' => 'Draft', 'slug' => 'draft']);

        $this->getJson('/api/diseases')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Aeromonas')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category', 'severity', 'thumbnail_url', 'fish_types', 'recovery_days']]]);

        $this->getJson('/api/diseases?category=parasite')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'white-spot-ich')
            ->assertJsonPath('data.0.thumbnail_url', 'https://cdn.melak.id/diseases/ich-1.webp');

        $this->getJson('/api/diseases?fish_type=Lele')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/diseases?severity=kritis')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_disease_detail_returns_full_guides(): void
    {
        $this->fullDisease();

        $response = $this->getJson('/api/diseases/white-spot-ich');

        $response->assertOk()
            ->assertJsonPath('data.name', 'White Spot (Ich)')
            ->assertJsonPath('data.severity', 'berat')
            ->assertJsonPath('data.symptoms.0', 'Bintik putih seperti garam di kulit & sirip')
            ->assertJsonPath('data.fish_types.0', 'Lele')
            ->assertJsonPath('data.images.0.url', 'https://cdn.melak.id/diseases/ich-1.webp')
            ->assertJsonPath('data.treatments.0.title', 'Pisahkan ikan yang terinfeksi')
            ->assertJsonPath('data.treatments.1.medicines.0.name', 'Garam ikan')
            ->assertJsonPath('data.preventions.0', 'Karantina ikan baru minimal 7 hari')
            ->assertJsonPath('data.recovery_days.min', 5)
            ->assertJsonPath('data.recovery_days.max', 14)
            ->assertJsonPath('data.related_forum_posts_count', 0);
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
            ->assertJsonPath('data.slug', 'white-spot-ich');
    }

    public function test_search_matches_name_symptoms_and_fish_type(): void
    {
        $this->fullDisease();
        Disease::factory()->create([
            'name' => 'Saprolegnia',
            'name_en' => 'Water Mold',
            'slug' => 'saprolegnia',
            'category' => 'fungi',
            'description' => 'Jamur air putih pada kulit dan sirip.',
            'causes' => 'Jamur Saprolegnia parasitica.',
            'symptoms' => 'Selaput kapas berwarna putih.',
        ]);

        $this->getJson('/api/diseases/search?q=bintik')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'white-spot-ich')
            ->assertJsonPath('data.0.thumbnail_url', 'https://cdn.melak.id/diseases/ich-1.webp')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category', 'severity', 'thumbnail_url', 'snippet']]]);

        $this->getJson('/api/diseases/search?q=lele')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/diseases/search?q=saprolegnia')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/diseases/search?q=x')
            ->assertStatus(422);
    }

    public function test_similar_diseases_returns_max_four(): void
    {
        $this->fullDisease();
        Disease::factory()->create(['category' => 'parasite', 'name' => 'Trichodina', 'slug' => 'trichodina']);
        Disease::factory()->create(['category' => 'bacteria', 'name' => 'Columnaris', 'slug' => 'columnaris']);

        $response = $this->getJson('/api/diseases/white-spot-ich/similar');

        $response->assertOk()->assertJsonPath('data.0.slug', 'trichodina');
        $this->assertLessThanOrEqual(4, count($response->json('data')));
        $this->assertNotContains('white-spot-ich', array_column($response->json('data'), 'slug'));
    }

    public function test_unpublished_disease_is_not_public(): void
    {
        Disease::factory()->create(['slug' => 'rahasia', 'name' => 'Rahasia', 'is_published' => false]);

        $this->getJson('/api/diseases/rahasia')->assertStatus(404);
        $this->getJson('/api/diseases/rahasia/similar')->assertStatus(404);
    }
}
