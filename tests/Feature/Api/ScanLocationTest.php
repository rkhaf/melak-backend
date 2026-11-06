<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserPond;
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
            'images' => [UploadedFile::fake()->image('ikan.jpg', 640, 480)],
            'fish_type' => 'Nila',
            'location_lat' => -6.3275,
            'location_lng' => 108.3250,
        ]);

        $response->assertStatus(202);

        $scan = FishScan::find($response->json('data.scan_id'));

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
            'images' => [UploadedFile::fake()->image('ikan.jpg', 640, 480)],
        ]);

        $response->assertStatus(202);

        $this->assertNull(FishScan::find($response->json('data.scan_id'))->kecamatan);
    }

    public function test_create_scan_resolves_kecamatan_from_pond_when_no_gps(): void
    {
        $user = User::factory()->create();
        $pond = UserPond::create([
            'name' => 'Kolam Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);

        $response = $this->actingAs($user)->post('/api/scans', [
            'images' => [UploadedFile::fake()->image('ikan.jpg', 640, 480)],
            'pond_id' => $pond->id,
        ]);

        $response->assertStatus(202);

        $this->assertSame(
            'Jatibarang',
            FishScan::find($response->json('data.scan_id'))->kecamatan
        );
    }

    public function test_create_scan_rejects_out_of_range_location(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/scans', [
            'images' => [UploadedFile::fake()->image('ikan.jpg', 640, 480)],
            'location_lat' => 999,
            'location_lng' => 108.3250,
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['location_lat']]);
    }

    public function test_heatmap_uses_scan_coordinates_without_pond(): void
    {
        // `kecamatan` scan sengaja dibiarkan null — endpoint harus tetap bisa
        // mengelompokkan via pemetaan koordinat (jalur fallback).
        $user = User::factory()->create(['province' => 'Jawa Barat']);
        $disease = Disease::factory()->create(['name' => 'Aeromoniasis', 'slug' => 'aeromoniasis']);

        $scan = FishScan::factory()->create([
            'user_id' => $user->id,
            'pond_id' => null,
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
            ->assertJsonPath('data.0.top_disease', 'Aeromoniasis')
            ->assertJsonPath('data.0.lat', -6.3275)
            ->assertJsonPath('data.0.lng', 108.325);
    }

    private function makePondWithScan(User $user, Disease $disease, ?array $scanLocation): void
    {
        $pond = UserPond::create([
            'user_id' => $user->id,
            'name' => 'Tambak Test',
            'location_lat' => -6.9,
            'location_lng' => 107.6,
            'location_label' => 'Indramayu, Jawa Barat',
            'health_score' => 90,
        ]);

        $scan = FishScan::factory()->create(array_merge([
            'user_id' => $user->id,
            'pond_id' => $pond->id,
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

    public function test_pins_prefer_scan_gps_over_pond_coordinates(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Aeromoniasis', 'slug' => 'aeromoniasis-pins']);

        $this->makePondWithScan($user, $disease, [-6.3275, 108.3250]);

        $this->getJson('/api/map/pins?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.3275)
            ->assertJsonPath('data.0.lng', 108.325)
            ->assertJsonPath('data.0.pond_name', 'Tambak Test');
    }

    public function test_pins_fall_back_to_pond_coordinates_without_scan_gps(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'White Spot', 'slug' => 'white-spot-fallback']);

        $this->makePondWithScan($user, $disease, null);

        $this->getJson('/api/map/pins?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lat', -6.9)
            ->assertJsonPath('data.0.lng', 107.6);
    }

    public function test_pins_include_scan_without_pond_when_it_has_gps(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Velvet', 'slug' => 'velvet-pins']);

        $scan = FishScan::factory()->create([
            'user_id' => $user->id,
            'pond_id' => null,
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
            ->assertJsonPath('data.0.pond_name', null);
    }
}
