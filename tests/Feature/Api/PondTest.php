<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserPond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $user = User::factory()->create();
        User::whereKey($user->id)->toBase()->update(['created_at' => now()->subDays(30)]);

        return $user->refresh();
    }

    private function doneScan(UserPond $pond, Disease $disease, array $overrides = []): FishScan
    {
        $scan = FishScan::factory()->create([
            'user_id' => $pond->user_id,
            'pond_id' => $pond->id,
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

    public function test_pond_crud_requires_authentication(): void
    {
        $this->getJson('/api/ponds')->assertStatus(401)->assertJsonPath('success', false);
        $this->postJson('/api/ponds', [])->assertStatus(401);
    }

    public function test_pond_store_list_update_and_delete(): void
    {
        $user = $this->owner();

        $created = $this->actingAs($user)->postJson('/api/ponds', [
            'name' => 'Kolam A',
            'location_lat' => -6.5531,
            'location_lng' => 107.4561,
            'location_label' => 'Indramayu, Jawa Barat',
            'area_m2' => 120,
            'capacity' => 5000,
            'fish_types' => ['Lele', 'Nila'],
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.name', 'Kolam A')
            ->assertJsonPath('data.health_score', 100)
            ->assertJsonPath('data.fish_types.0', 'Lele')
            ->assertJsonPath('data.location_lat', -6.5531);

        $pondId = $created->json('data.id');

        $this->actingAs($user)->getJson('/api/ponds')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Kolam A')
            ->assertJsonPath('data.0.health_score', 100);

        $this->actingAs($user)->putJson("/api/ponds/{$pondId}", ['name' => 'Kolam Baru', 'capacity' => 7000])
            ->assertOk()
            ->assertJsonPath('data.name', 'Kolam Baru')
            ->assertJsonPath('data.capacity', 7000);

        $this->actingAs($user)->deleteJson("/api/ponds/{$pondId}")->assertNoContent();
        $this->assertDatabaseMissing('user_ponds', ['id' => $pondId]);
    }

    public function test_pond_show_returns_detail_and_enforces_ownership(): void
    {
        $user = $this->owner();
        $stranger = $this->owner();

        $pond = UserPond::create([
            'user_id' => $user->id,
            'name' => 'Tambak Edisi',
            'location_lat' => -6.3275,
            'location_lng' => 108.325,
            'location_label' => 'Indramayu, Jawa Barat',
            'area_m2' => 150,
            'capacity' => 4000,
            'fish_types' => ['Lele', 'Nila'],
            'health_score' => 90,
        ]);

        // Tanpa token → 401. Dicek paling awal: actingAs() mengunci guard untuk
        // request berikutnya dalam satu test, jadi setelah actingAs() baris ini
        // akan terbaca sebagai "masih sebagai stranger" (404).
        $this->getJson("/api/ponds/{$pond->id}")->assertStatus(401);

        // Form "Ubah Tambak" & laporan kesehatan memanggil endpoint ini (API.md §7.14).
        $this->actingAs($user)->getJson("/api/ponds/{$pond->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Tambak Edisi')
            ->assertJsonPath('data.location_lat', -6.3275)
            ->assertJsonPath('data.location_label', 'Indramayu, Jawa Barat')
            ->assertJsonPath('data.area_m2', 150)
            ->assertJsonPath('data.capacity', 4000)
            ->assertJsonPath('data.fish_types.0', 'Lele')
            ->assertJsonPath('data.health_score', 90);

        $this->actingAs($stranger)->getJson("/api/ponds/{$pond->id}")->assertStatus(404);
    }

    public function test_pond_validation_and_ownership(): void
    {
        $user = $this->owner();
        $stranger = $this->owner();

        $this->actingAs($user)->postJson('/api/ponds', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $pond = UserPond::create(['user_id' => $user->id, 'name' => 'Kolam Rahasia', 'health_score' => 100]);

        $this->actingAs($stranger)->getJson("/api/ponds/{$pond->id}/health-report")->assertStatus(404);
        $this->actingAs($stranger)->putJson("/api/ponds/{$pond->id}", ['name' => 'Dicuri'])->assertStatus(404);
        $this->actingAs($stranger)->deleteJson("/api/ponds/{$pond->id}")->assertStatus(404);
    }

    public function test_health_report_computes_score_trend_and_repeat_alert(): void
    {
        $user = $this->owner();
        $pond = UserPond::create(['user_id' => $user->id, 'name' => 'Kolam A', 'health_score' => 100]);

        $berat = Disease::factory()->create(['severity' => 'berat', 'name' => 'Aeromonas', 'slug' => 'aeromonas']);
        $ringan = Disease::factory()->create(['severity' => 'ringan', 'name' => 'Kutu Air', 'slug' => 'kutu-air']);

        $this->doneScan($pond, $berat);
        $this->doneScan($pond, $berat);
        $this->doneScan($pond, $ringan);

        $response = $this->actingAs($user)->getJson("/api/ponds/{$pond->id}/health-report?period=7d");

        $response->assertOk()
            ->assertJsonPath('data.pond.name', 'Kolam A')
            ->assertJsonPath('data.health_score', 66)
            ->assertJsonPath('data.repeat_disease_alert.name', 'Aeromonas')
            ->assertJsonPath('data.repeat_disease_alert.occurrences', 2);

        $trend = $response->json('data.trend');
        $this->assertCount(7, $trend);
        $this->assertSame(now()->toDateString(), $trend[6]['date']);
        $this->assertSame(3, (int) $trend[6]['scans']);
        $this->assertContains('Aeromonas', $trend[6]['diseases']);

        $lastScan = $response->json('data.last_scan');
        $this->assertNotNull($lastScan['scan_id']);
        $this->assertSame(66, (int) UserPond::find($pond->id)->health_score);
    }

    public function test_health_report_without_alert_and_period_validation(): void
    {
        $user = $this->owner();
        $pond = UserPond::create(['user_id' => $user->id, 'name' => 'Kolam Tenang', 'health_score' => 100]);

        $response = $this->actingAs($user)->getJson("/api/ponds/{$pond->id}/health-report");
        $response->assertOk()
            ->assertJsonPath('data.health_score', 100)
            ->assertJsonPath('data.repeat_disease_alert', null)
            ->assertJsonPath('data.last_scan', null);

        $this->assertCount(7, $response->json('data.trend'));

        $this->actingAs($user)->getJson("/api/ponds/{$pond->id}/health-report?period=14d")
            ->assertStatus(422);
    }
}
