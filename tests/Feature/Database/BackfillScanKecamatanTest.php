<?php

namespace Tests\Feature\Database;

use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserGarden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test `php artisan scan:backfill-kecamatan` (FR-07.3 — peta sebaran per
 * kecamatan; menutup scan yang dibuat sebelum kolom kecamatan tersedia).
 */
class BackfillScanKecamatanTest extends TestCase
{
    use RefreshDatabase;

    private function oldScan(array $attributes = []): PlantScan
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $scan = PlantScan::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'done',
            'kecamatan' => null,
        ], $attributes));

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 80,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        return $scan;
    }

    public function test_backfills_from_scan_gps_garden_coordinates_and_keeps_unknown_null(): void
    {
        $withGps = $this->oldScan([
            'location_lat' => -6.445695,
            'location_lng' => 107.936725,
        ]);

        $user = User::factory()->create();
        $garden = UserGarden::create([
            'name' => 'Kebun Uji',
            'user_id' => $user->id,
            'location_lat' => -6.44973,
            'location_lng' => 108.317185,
        ]);
        $withGarden = $this->oldScan(['user_id' => $user->id, 'garden_id' => $garden->id]);

        $noCoords = $this->oldScan();

        $outside = $this->oldScan([
            'location_lat' => -6.2088,
            'location_lng' => 106.8456,
        ]);

        $this->artisan('scan:backfill-kecamatan')
            ->expectsOutputToContain('kecamatan terisi: 2, tidak terpetakan: 2')
            ->assertExitCode(0);

        $this->assertSame('Haurgeulis', $withGps->fresh()->kecamatan);
        $this->assertSame('Jatibarang', $withGarden->fresh()->kecamatan);
        $this->assertNull($noCoords->fresh()->kecamatan);
        $this->assertNull($outside->fresh()->kecamatan);
    }

    public function test_dry_run_reports_without_persisting(): void
    {
        $scan = $this->oldScan([
            'location_lat' => -6.445695,
            'location_lng' => 107.936725,
        ]);

        $this->artisan('scan:backfill-kecamatan', ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run] kecamatan terisi: 1')
            ->assertExitCode(0);

        $this->assertNull($scan->fresh()->kecamatan);
    }

    public function test_is_idempotent_for_already_filled_scans(): void
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();
        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'status' => 'done',
            'location_lat' => -6.445695,
            'location_lng' => 107.936725,
            'kecamatan' => 'Haurgeulis',
        ]);
        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 80,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        $this->artisan('scan:backfill-kecamatan')
            ->expectsOutputToContain('kecamatan terisi: 0, tidak terpetakan: 0')
            ->assertExitCode(0);

        $this->assertSame('Haurgeulis', $scan->fresh()->kecamatan);
    }
}
