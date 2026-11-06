<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserPond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test GET /api/map/heatmap — agregasi kasus per kecamatan (FR-07.3,
 * fokus Kab. Indramayu; API.md §7.11).
 */
class HeatmapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat scan selesai + hasil prediksi; `kecamatan` bisa di-set eksplisit
     * (jalur kolom) atau dibiarkan null (jalur fallback pemetaan koordinat).
     */
    private function caseScan(
        User $user,
        Disease $disease,
        ?array $gps = null,
        ?UserPond $pond = null,
        ?string $kecamatan = null,
    ): void {
        $scan = FishScan::factory()->create([
            'user_id' => $user->id,
            'pond_id' => $pond?->id,
            'status' => 'done',
            'location_lat' => $gps[0] ?? null,
            'location_lng' => $gps[1] ?? null,
            'kecamatan' => $kecamatan,
        ]);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 90,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);
    }

    public function test_heatmap_is_public_and_groups_by_kecamatan(): void
    {
        // Dua scan di Jatibarang (GPS), satu di Haurgeulis — tanpa `kecamatan`
        // tersimpan, jadi dikelompokkan lewat pemetaan koordinat.
        $this->caseScan(User::factory()->create(), Disease::factory()->create(['name' => 'Aeromoniasis']), [-6.44973, 108.317185]);
        $this->caseScan(User::factory()->create(), Disease::factory()->create(['name' => 'Aeromoniasis']), [-6.44970, 108.31710]);
        $this->caseScan(User::factory()->create(), Disease::factory()->create(['name' => 'White Spot (Ich)']), [-6.445695, 107.936725]);

        $this->getJson('/api/map/heatmap?days=30')
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Jatibarang')
            ->assertJsonPath('data.0.cases', 2)
            ->assertJsonPath('data.0.top_disease', 'Aeromoniasis')
            ->assertJsonPath('data.1.kecamatan', 'Haurgeulis')
            ->assertJsonPath('data.1.cases', 1)
            ->assertJsonStructure(['data' => [['kecamatan', 'lat', 'lng', 'cases', 'top_disease']]])
            ->assertJsonMissingPath('data.0.province');
    }

    public function test_heatmap_uses_kecamatan_column_when_available(): void
    {
        $this->caseScan(
            User::factory()->create(),
            Disease::factory()->create(['name' => 'Aeromoniasis']),
            [-6.44973, 108.317185],
            kecamatan: 'Jatibarang',
        );

        $this->getJson('/api/map/heatmap?days=30')
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Jatibarang')
            ->assertJsonPath('data.0.cases', 1);
    }

    public function test_heatmap_falls_back_to_pond_coordinates(): void
    {
        $user = User::factory()->create(['province' => 'Jawa Barat']);
        $pond = UserPond::create([
            'name' => 'Kolam Uji',
            'user_id' => $user->id,
            'location_lat' => -6.319895,
            'location_lng' => 108.000665,
        ]);

        $this->caseScan($user, Disease::factory()->create(), pond: $pond);

        $this->getJson('/api/map/heatmap?days=30')
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Patrol');
    }

    public function test_heatmap_skips_scans_without_any_coordinates(): void
    {
        // Tanpa GPS dan tanpa kolam berkoordinat → tidak bisa ditempatkan di peta.
        $this->caseScan(User::factory()->create(), Disease::factory()->create());

        $this->getJson('/api/map/heatmap?days=30')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_heatmap_filters_by_province_and_disease(): void
    {
        $jabar = User::factory()->create(['province' => 'Jawa Barat']);
        $jateng = User::factory()->create(['province' => 'Jawa Tengah']);
        $disease = Disease::factory()->create(['name' => 'Aeromoniasis']);
        $other = Disease::factory()->create(['name' => 'White Spot (Ich)']);

        $this->caseScan($jabar, $disease, [-6.44973, 108.317185]);
        $this->caseScan($jateng, $disease, [-6.445695, 107.936725]);
        $this->caseScan($jabar, $other, [-6.319895, 108.000665]);

        $this->getJson('/api/map/heatmap?days=30&province='.urlencode('Jawa Barat'))
            ->assertOk()
            ->assertJsonPath('data.0.kecamatan', 'Jatibarang')
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/map/heatmap?days=30&disease_id='.$disease->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
