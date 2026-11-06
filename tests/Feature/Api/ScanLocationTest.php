<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserGarden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_create_scan_stores_location_coordinates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/scans', [
            'images' => [UploadedFile::fake()->image('tanaman.jpg', 640, 480)],
            'plant_type' => 'Jagung',
            'location_lat' => -6.3275,
            'location_lng' => 108.3250,
        ]);

        $response->assertStatus(202);

        $scan = PlantScan::find($response->json('data.scan_id'));

        $this->assertNotNull($scan);
        $this->assertEqualsWithDelta(-6.3275, (float) $scan->location_lat, 0.0001);
        $this->assertEqualsWithDelta(108.3250, (float) $scan->location_lng, 0.0001);
        // GPS di dalam polygon Kab. Indramayu → kecamatan terpetakan (FR-07.3).
        $this->assertSame('Indramayu', $scan->kecamatan);
    }

    public function test_create_scan_without_location_leaves_kecamatan_null(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/scans', [
            'images' => [UploadedFile::fake()->image('tanaman.jpg', 640, 480)],
        ]);

        $response->assertStatus(202);

        $this->assertNull(PlantScan::find($response->json('data.scan_id'))->kecamatan);
    }

    public function test_create_scan_resolves_kecamatan_from_garden_when_no_gps(): void
    {
        $user = User::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);

        $response = $this->actingAs($user)->post('/api/scans', [
            'images' => [UploadedFile::fake()->image('tanaman.jpg', 640, 480)],
            'garden_id' => $garden->id,
        ]);

        $response->assertStatus(202);

        $this->assertSame(
            'Jatibarang',
            PlantScan::find($response->json('data.scan_id'))->kecamatan
        );
    }

    public function test_create_scan_rejects_out_of_range_location(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/scans', [
            'images' => [UploadedFile::fake()->image('tanaman.jpg', 640, 480)],
            'location_lat' => 999,
            'location_lng' => 108.3250,
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['location_lat']]);
    }

    public function test_heatmap_uses_scan_coordinates_without_garden(): void
    {
        // `kecamatan` scan sengaja dibiarkan null — endpoint harus tetap bisa
        // mengelompokkan via pemetaan koordinat (jalur fallback).
        $user = User::factory()->create(['province' => 'Jawa Barat']);
        $disease = Disease::factory()->create(['name' => 'Bercak Bakteri Tomat', 'slug' => 'bercak-bakteri-tomat']);

        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => null,
            'status' => 'done',
            'location_lat' => -6.3275,
            'location_lng' => 108.3250,
        ]);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 70,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);

        $this->getJson('/api/map/heatmap?days=30')
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Indramayu')
            ->assertJsonPath('data.0.cases', 1)
            ->assertJsonPath('data.0.top_disease', 'Bercak Bakteri Tomat')
            ->assertJsonPath('data.0.lat', -6.3275)
            ->assertJsonPath('data.0.lng', 108.325);
    }

    private function makeGardenWithScan(User $user, Disease $disease, ?array $scanLocation): void
    {
        $garden = UserGarden::create([
            'user_id' => $user->id,
            'name' => 'Lahan Test',
            'location_lat' => -6.9,
            'location_lng' => 107.6,
            'location_label' => 'Indramayu, Jawa Barat',
            'health_score' => 90,
        ]);

        $scan = PlantScan::factory()->create(array_merge([
            'user_id' => $user->id,
            'garden_id' => $garden->id,
            'status' => 'done',
        ], $scanLocation === null ? [] : [
            'location_lat' => $scanLocation[0],
            'location_lng' => $scanLocation[1],
        ]));

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 70,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);
    }

    public function test_pins_prefer_scan_gps_over_garden_coordinates(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Bercak Bakteri Tomat', 'slug' => 'bercak-bakteri-tomat-pins']);

        $this->makeGardenWithScan($user, $disease, [-6.3275, 108.3250]);

        $this->getJson('/api/map/pins?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.3275)
            ->assertJsonPath('data.0.lng', 108.325)
            ->assertJsonMissingPath('data.0.garden_name')
            ->assertJsonMissingPath('data.0.location_label');
    }

    public function test_pins_fall_back_to_garden_coordinates_without_scan_gps(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Bercak Tomat', 'slug' => 'bercak-tomat-fallback']);

        $this->makeGardenWithScan($user, $disease, null);

        $this->getJson('/api/map/pins?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.9)
            ->assertJsonPath('data.0.lng', 107.6);
    }

    public function test_pins_include_scan_without_garden_when_it_has_gps(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Velvet', 'slug' => 'velvet-pins']);

        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'garden_id' => null,
            'status' => 'done',
            'location_lat' => -6.35,
            'location_lng' => 108.30,
        ]);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 70,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);

        $this->getJson('/api/map/pins?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.35)
            ->assertJsonPath('data.0.lng', 108.3)
            ->assertJsonPath('data.0.garden_name', null);
    }
}
