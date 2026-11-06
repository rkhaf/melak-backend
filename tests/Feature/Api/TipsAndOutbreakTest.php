<?php

namespace Tests\Feature\Api;

use App\Models\AppTip;
use App\Models\Disease;
use App\Models\FishScan;
use App\Models\Notification;
use App\Models\OutbreakAlert;
use App\Models\ScanResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipsAndOutbreakTest extends TestCase
{
    use RefreshDatabase;

    private function scanWithDisease(User $user, Disease $disease): void
    {
        $scan = FishScan::factory()->create(['user_id' => $user->id, 'status' => 'done']);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 88,
            'top_predictions' => [],
            'low_confidence_warning' => false,
            'model_version' => 'fake-v1',
        ]);
    }

    public function test_tips_today_returns_rotated_tip_or_404(): void
    {
        $this->getJson('/api/tips/today')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        AppTip::create(['content' => 'Ganti air kolam 20% tiap minggu.', 'category' => 'kualitas_air', 'is_active' => true]);
        AppTip::create(['content' => 'Pakan berlebih memicu kembung.', 'category' => 'pakan', 'is_active' => true]);
        AppTip::create(['content' => 'Tips nonaktif.', 'category' => 'umum', 'is_active' => false]);

        $first = $this->getJson('/api/tips/today');
        $first->assertOk()
            ->assertJsonStructure(['data' => ['id', 'content', 'category', 'published_at']]);

        $second = $this->getJson('/api/tips/today');
        $this->assertSame($first->json('data.id'), $second->json('data.id'), 'rotation stabil dalam satu hari');

        AppTip::create([
            'content' => 'Tips hari ini.',
            'category' => 'umum',
            'is_active' => true,
            'published_on' => now()->toDateString(),
        ]);

        $this->getJson('/api/tips/today')
            ->assertOk()
            ->assertJsonPath('data.content', 'Tips hari ini.');
    }

    public function test_outbreaks_list_requires_auth_and_shows_active_alerts(): void
    {
        $this->getJson('/api/outbreaks')->assertStatus(401);

        $user = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'White Spot (Ich)', 'slug' => 'white-spot-ich', 'severity' => 'berat']);

        OutbreakAlert::create([
            'disease_id' => $disease->id,
            'province' => 'Jawa Barat',
            'cases_7d' => 9,
            'threshold' => 5,
            'message' => '9 kasus White Spot (Ich) terlaporkan di Jawa Barat dalam 7 hari terakhir.',
            'status' => 'active',
        ]);
        OutbreakAlert::create([
            'disease_id' => $disease->id,
            'province' => 'Bali',
            'cases_7d' => 2,
            'threshold' => 5,
            'message' => 'Kasus menurun.',
            'status' => 'expired',
        ]);

        $response = $this->actingAs($user)->getJson('/api/outbreaks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.province', 'Jawa Barat')
            ->assertJsonPath('data.0.cases_7d', 9)
            ->assertJsonPath('data.0.threshold', 5)
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.disease.name', 'White Spot (Ich)')
            ->assertJsonPath('data.0.disease.severity', 'berat')
            ->assertJsonStructure(['data' => [['id', 'disease', 'province', 'cases_7d', 'threshold', 'message', 'status', 'created_at']]]);
    }

    public function test_aggregate_command_creates_alerts_and_notifies_province_users(): void
    {
        $disease = Disease::factory()->create(['name' => 'Columnaris', 'slug' => 'columnaris', 'severity' => 'sedang']);

        $provinceUsers = User::factory()->count(5)->create(['province' => 'Jawa Barat']);
        $outsider = User::factory()->create(['province' => 'Sumatera Utara']);

        foreach ($provinceUsers as $user) {
            $this->scanWithDisease($user, $disease);
        }
        $this->scanWithDisease($outsider, $disease);

        $this->artisan('outbreaks:aggregate', ['--threshold' => 5])->assertSuccessful();

        $alert = OutbreakAlert::where('disease_id', $disease->id)->where('province', 'Jawa Barat')->first();
        $this->assertNotNull($alert);
        $this->assertSame('active', $alert->status);
        $this->assertSame(5, (int) $alert->cases_7d);
        $this->assertSame(5, (int) $alert->threshold);
        $this->assertStringContainsString('Columnaris', $alert->message);

        $this->assertDatabaseHas('notifications', ['type' => 'outbreak']);
        $this->assertSame(
            5,
            Notification::where('type', 'outbreak')->where('title', 'Alert wabah di Jawa Barat')->count()
        );
        $this->assertSame(
            0,
            Notification::where('type', 'outbreak')->where('user_id', $outsider->id)->count()
        );

        $this->actingAs($provinceUsers->first())->getJson('/api/outbreaks')
            ->assertJsonCount(1, 'data');
    }

    public function test_aggregate_command_expires_alerts_below_threshold(): void
    {
        $disease = Disease::factory()->create(['name' => 'Velvet', 'slug' => 'velvet']);

        OutbreakAlert::create([
            'disease_id' => $disease->id,
            'province' => 'Jawa Barat',
            'cases_7d' => 9,
            'threshold' => 5,
            'message' => 'Lama.',
            'status' => 'active',
        ]);

        $this->artisan('outbreaks:aggregate', ['--threshold' => 5])->assertSuccessful();

        $this->assertDatabaseHas('outbreak_alerts', [
            'disease_id' => $disease->id,
            'province' => 'Jawa Barat',
            'status' => 'expired',
        ]);
    }

    public function test_aggregate_ignores_old_and_failed_scans(): void
    {
        $disease = Disease::factory()->create(['name' => 'Aeromonas', 'slug' => 'aeromonas']);

        $users = User::factory()->count(6)->create(['province' => 'Jawa Tengah']);

        foreach ($users as $user) {
            $scan = FishScan::factory()->failed()->create(['user_id' => $user->id]);
            ScanResult::create([
                'scan_id' => $scan->id,
                'disease_id' => $disease->id,
                'confidence_score' => 70,
                'top_predictions' => [],
                'low_confidence_warning' => false,
                'model_version' => 'fake-v1',
            ]);
        }

        $old = User::factory()->create(['province' => 'Jawa Tengah']);
        $this->scanWithDisease($old, $disease);
        FishScan::where('user_id', $old->id)->toBase()->update(['created_at' => now()->subDays(30)]);

        $this->artisan('outbreaks:aggregate', ['--threshold' => 5])->assertSuccessful();

        $this->assertSame(0, OutbreakAlert::where('province', 'Jawa Tengah')->count());
    }
}
