<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserGarden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test endpoint GET /api/map/pins (FR-07.3 — GIS per-scan).
 */
class MapPinsTest extends TestCase
{
    use RefreshDatabase;

    private function createScanWithGarden(
        User $user,
        Disease $disease,
        UserGarden $garden,
        ?array $gps = null,
        ?string $kecamatan = null,
    ): PlantScan {
        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => $garden->id,
            'status' => 'done',
            'plant_type' => 'Tomat',
            'location_lat' => $gps[0] ?? null,
            'location_lng' => $gps[1] ?? null,
            'kecamatan' => $kecamatan,
        ]);
        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 88.5,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        return $scan;
    }

    public function test_pins_are_public_and_return_garden_coordinates_without_owner_details(): void
    {
        $user = User::factory()->create(['name' => 'Pemilik Rahasia']);
        $disease = Disease::factory()->create(['name' => 'Aeromoniasis']);
        $garden = UserGarden::create([
            'name' => 'Lahan Rahasia',
            'user_id' => $user->id,
            'location_label' => 'Indramayu, Jawa Barat',
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $this->createScanWithGarden($user, $disease, $garden);

        $response = $this->getJson('/api/map/pins?days=7')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.44973)
            ->assertJsonPath('data.0.lng', 108.317185)
            ->assertJsonPath('data.0.disease_name', 'Aeromoniasis')
            ->assertJsonPath('data.0.plant_type', 'Tomat')
            ->assertJsonPath('data.0.kecamatan', 'Jatibarang')
            ->assertJsonStructure(['data' => [[
                'id', 'lat', 'lng', 'kecamatan', 'disease_id', 'disease_name',
                'severity', 'confidence_score', 'plant_type', 'scanned_at',
            ]]]);

        // Privasi: nama kebun, label lokasi, dan identitas pemilik tidak ikut.
        $response->assertJsonMissingPath('data.0.garden_name')
            ->assertJsonMissingPath('data.0.location_label')
            ->assertJsonPath('data.0.garden_name', null)
            ->assertJsonPath('data.0.location_label', null);

        $this->assertStringNotContainsString('Lahan Rahasia', $response->getContent());
        $this->assertStringNotContainsString('Pemilik Rahasia', $response->getContent());
    }

    public function test_pin_prefers_scan_gps_over_garden_coordinates(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $this->createScanWithGarden(
            $user,
            $disease,
            $garden,
            gps: [-6.445695, 107.936725],
            kecamatan: 'Haurgeulis',
        );

        $this->getJson('/api/map/pins?days=7')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.445695)
            ->assertJsonPath('data.0.lng', 107.936725)
            ->assertJsonPath('data.0.kecamatan', 'Haurgeulis');
    }

    public function test_pin_is_returned_for_scan_without_garden_using_gps(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $this->createScanWithGarden($user, $disease, $garden);

        // Scan kedua tanpa kebun, hanya GPS.
        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => null,
            'status' => 'done',
            'location_lat' => -6.319895,
            'location_lng' => 108.000665,
            'kecamatan' => 'Patrol',
        ]);
        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 60,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        $response = $this->getJson('/api/map/pins?days=7')->assertOk();

        $pins = collect($response->json('data'));
        $this->assertCount(2, $pins);

        $gpsPin = $pins->firstWhere('kecamatan', 'Patrol');
        $this->assertNotNull($gpsPin, 'Pin dari scan tanpa kebun harus tetap muncul');
        $this->assertSame(-6.319895, $gpsPin['lat']);
        $this->assertSame(108.000665, $gpsPin['lng']);
    }

    public function test_pin_resolves_kecamatan_from_coordinates_when_column_is_null(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $this->createScanWithGarden($user, $disease, $garden);

        $this->getJson('/api/map/pins?days=7')
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Jatibarang');
    }

    public function test_pins_are_empty_when_no_scan_in_range(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => $garden->id,
            'status' => 'done',
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
            'kecamatan' => 'Jatibarang',
            'created_at' => now()->subDays(10),
        ]);
        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 70,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        $this->getJson('/api/map/pins?days=7')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_pins_filter_by_disease_id(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $other = Disease::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $this->createScanWithGarden($user, $disease, $garden);

        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => null,
            'status' => 'done',
            'location_lat' => -6.319895,
            'location_lng' => 108.000665,
            'kecamatan' => 'Patrol',
        ]);
        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $other->id,
            'confidence_score' => 55,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        $this->getJson('/api/map/pins?days=7&disease_id='.$disease->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.disease_id', $disease->id);
    }
}
