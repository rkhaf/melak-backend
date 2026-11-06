<?php

namespace Tests\Feature\Database;

use App\Models\Disease;
use App\Models\DiseasePlantType;
use App\Models\DiseasePrevention;
use App\Models\DiseaseTreatment;
use Database\Seeders\PlantDiseaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantDiseaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_domestic_plant_diseases_and_plant_types(): void
    {
        $this->seed(PlantDiseaseSeeder::class);

        $this->assertSame(11, Disease::count());
        $this->assertDatabaseHas('diseases', [
            'slug' => 'tomato-bacterial-spot',
            'name' => 'Bercak Bakteri pada Tomat',
        ]);
        $this->assertDatabaseHas('disease_plant_types', [
            'plant_type' => 'Tomat',
        ]);
        $this->assertDatabaseHas('disease_plant_types', [
            'plant_type' => 'Jagung',
        ]);

        foreach (Disease::with(['plantTypes', 'preventions'])->get() as $disease) {
            $this->assertTrue($disease->is_published);
            $this->assertNotEmpty($disease->description);
            $this->assertNotEmpty($disease->causes);
            $this->assertNotEmpty($disease->symptoms);
            $this->assertNotEmpty($disease->plantTypes);
            $this->assertNotEmpty($disease->preventions);
        }
    }

    public function test_seeding_twice_is_idempotent_and_preserves_disease_ids(): void
    {
        $this->seed(PlantDiseaseSeeder::class);
        $idBefore = Disease::query()->where('slug', 'tomato-bacterial-spot')->value('id');
        $countsBefore = $this->counts();

        $this->seed(PlantDiseaseSeeder::class);

        $this->assertSame($countsBefore, $this->counts());
        $this->assertSame(
            $idBefore,
            Disease::query()->where('slug', 'tomato-bacterial-spot')->value('id'),
        );
    }

    public function test_legacy_fish_diseases_are_archived_without_deleting_history(): void
    {
        $legacy = Disease::factory()->create([
            'name' => 'Legacy Aeromoniasis',
            'slug' => 'aeromoniasis',
            'is_published' => true,
        ]);

        $this->seed(PlantDiseaseSeeder::class);

        $this->assertDatabaseHas('diseases', [
            'id' => $legacy->id,
            'slug' => 'aeromoniasis',
            'is_published' => false,
        ]);
    }

    public function test_seeded_plant_disease_is_available_through_api(): void
    {
        $this->seed(PlantDiseaseSeeder::class);

        $this->getJson('/api/diseases/tomato-bacterial-spot')
            ->assertOk()
            ->assertJsonPath('data.slug', 'tomato-bacterial-spot')
            ->assertJsonPath('data.plant_types.0', 'Tomat')
            ->assertJsonCount(2, 'data.treatments')
            ->assertJsonCount(2, 'data.preventions');
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    private function counts(): array
    {
        return [
            Disease::count(),
            DiseasePlantType::count(),
            DiseaseTreatment::count(),
            DiseasePrevention::count(),
        ];
    }
}
