<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserGarden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GardenTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $user = User::factory()->create();
        User::whereKey($user->id)->toBase()->update(['created_at' => now()->subDays(30)]);

        return $user->refresh();
    }

    private function doneScan(UserGarden $garden, Disease $disease, array $overrides = []): PlantScan
    {
        $scan = PlantScan::factory()->create([
            'user_id' => $garden->user_id,
            'garden_id' => $garden->id,
            'status' => 'done',
            ...$overrides,
        ]);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 88.0,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);

        return $scan;
    }

    public function test_garden_crud_requires_authentication(): void
    {
        $this->getJson('/api/gardens')->assertStatus(401)->assertJsonPath('success', false);
        $this->postJson('/api/gardens', [])->assertStatus(401);
    }

    public function test_garden_store_list_update_and_delete(): void
    {
        $user = $this->owner();

        $created = $this->actingAs($user)->postJson('/api/gardens', [
            'name' => 'Kebun A',
            'location_lat' => -6.5531,
            'location_lng' => 107.4561,
            'location_label' => 'Indramayu, Jawa Barat',
            'area_m2' => 120,
            'capacity' => 5000,
            'plant_types' => ['Tomat', 'Jagung'],
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.name', 'Kebun A')
            ->assertJsonPath('data.health_score', 100)
            ->assertJsonPath('data.plant_types.0', 'Tomat')
            ->assertJsonPath('data.location_lat', -6.5531);

        $gardenId = $created->json('data.id');

        $this->actingAs($user)->getJson('/api/gardens')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Kebun A')
            ->assertJsonPath('data.0.health_score', 100);

        $this->actingAs($user)->putJson("/api/gardens/{$gardenId}", ['name' => 'Kebun Baru', 'capacity' => 7000])
            ->assertOk()
            ->assertJsonPath('data.name', 'Kebun Baru')
            ->assertJsonPath('data.capacity', 7000);

        $this->actingAs($user)->deleteJson("/api/gardens/{$gardenId}")->assertNoContent();
        $this->assertDatabaseMissing('user_gardens', ['id' => $gardenId]);
    }

    public function test_garden_show_returns_detail_and_enforces_ownership(): void
    {
        $user = $this->owner();
        $stranger = $this->owner();

        $garden = UserGarden::create([
            'user_id' => $user->id,
            'name' => 'Lahan Edisi',
            'location_lat' => -6.3275,
            'location_lng' => 108.325,
            'location_label' => 'Indramayu, Jawa Barat',
            'area_m2' => 150,
            'capacity' => 4000,
            'plant_types' => ['Tomat', 'Jagung'],
            'health_score' => 90,
        ]);

        // Tanpa token → 401. Dicek paling awal: actingAs() mengunci guard untuk
        // request berikutnya dalam satu test, jadi setelah actingAs() baris ini
        // akan terbaca sebagai "masih sebagai stranger" (404).
        $this->getJson("/api/gardens/{$garden->id}")->assertStatus(401);

        // Form "Ubah Lahan" & laporan kesehatan memanggil endpoint ini (API.md §7.14).
        $this->actingAs($user)->getJson("/api/gardens/{$garden->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Lahan Edisi')
            ->assertJsonPath('data.location_lat', -6.3275)
            ->assertJsonPath('data.location_label', 'Indramayu, Jawa Barat')
            ->assertJsonPath('data.area_m2', 150)
            ->assertJsonPath('data.capacity', 4000)
            ->assertJsonPath('data.plant_types.0', 'Tomat')
            ->assertJsonPath('data.health_score', 90);

        $this->actingAs($stranger)->getJson("/api/gardens/{$garden->id}")->assertStatus(404);
    }

    public function test_garden_validation_and_ownership(): void
    {
        $user = $this->owner();
        $stranger = $this->owner();

        $this->actingAs($user)->postJson('/api/gardens', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $garden = UserGarden::create(['user_id' => $user->id, 'name' => 'Kebun Rahasia', 'health_score' => 100]);

        $this->actingAs($stranger)->getJson("/api/gardens/{$garden->id}/health-report")->assertStatus(404);
        $this->actingAs($stranger)->putJson("/api/gardens/{$garden->id}", ['name' => 'Dicuri'])->assertStatus(404);
        $this->actingAs($stranger)->deleteJson("/api/gardens/{$garden->id}")->assertStatus(404);
    }

    public function test_health_report_computes_score_trend_and_repeat_alert(): void
    {
        $user = $this->owner();
        $garden = UserGarden::create(['user_id' => $user->id, 'name' => 'Kebun A', 'health_score' => 100]);

        $berat = Disease::factory()->create(['severity' => 'berat', 'name' => 'Busuk Daun Tomat', 'slug' => 'busuk-daun-tomat']);
        $ringan = Disease::factory()->create(['severity' => 'ringan', 'name' => 'Kutu Air', 'slug' => 'kutu-air']);

        $this->doneScan($garden, $berat);
        $this->doneScan($garden, $berat);
        $this->doneScan($garden, $ringan);

        $response = $this->actingAs($user)->getJson("/api/gardens/{$garden->id}/health-report?period=7d");

        $response->assertOk()
            ->assertJsonPath('data.garden.name', 'Kebun A')
            ->assertJsonPath('data.health_score', 66)
            ->assertJsonPath('data.repeat_disease_alert.name', 'Busuk Daun Tomat')
            ->assertJsonPath('data.repeat_disease_alert.occurrences', 2);

        $trend = $response->json('data.trend');
        $this->assertCount(7, $trend);
        $this->assertSame(now()->toDateString(), $trend[6]['date']);
        $this->assertSame(3, (int) $trend[6]['scans']);
        $this->assertContains('Busuk Daun Tomat', $trend[6]['diseases']);

        $lastScan = $response->json('data.last_scan');
        $this->assertNotNull($lastScan['scan_id']);
        $this->assertSame(66, (int) UserGarden::find($garden->id)->health_score);
    }

    public function test_health_report_without_alert_and_period_validation(): void
    {
        $user = $this->owner();
        $garden = UserGarden::create(['user_id' => $user->id, 'name' => 'Kebun Tenang', 'health_score' => 100]);

        $response = $this->actingAs($user)->getJson("/api/gardens/{$garden->id}/health-report");
        $response->assertOk()
            ->assertJsonPath('data.health_score', 100)
            ->assertJsonPath('data.repeat_disease_alert', null)
            ->assertJsonPath('data.last_scan', null);

        $this->assertCount(7, $response->json('data.trend'));

        $this->actingAs($user)->getJson("/api/gardens/{$garden->id}/health-report?period=14d")
            ->assertStatus(422);
    }
}
