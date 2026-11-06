<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\NotificationSetting;
use App\Models\OutbreakAlert;
use App\Models\User;
use App\Models\UserPond;
use App\Notifications\WeeklyDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FR-06.2 — ringkasan mingguan (digest:weekly) + tipe notifikasi `weekly`.
 */
class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_digest_sends_inapp_and_mail_to_active_user_with_recent_scans(): void
    {
        $user = User::factory()->create();
        FishScan::factory()->create(['user_id' => $user->id]);
        UserPond::create(['user_id' => $user->id, 'name' => 'Kolam Uji']);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'weekly',
            'title' => 'Ringkasan mingguan '.now()->subDays(7)->format('d M Y').' - '.now()->format('d M Y'),
        ]);
        Notification::assertSentTo($user, WeeklyDigest::class);
    }

    public function test_digest_skips_users_without_weekly_activity(): void
    {
        $idle = User::factory()->create();
        UserPond::create(['user_id' => $idle->id, 'name' => 'Kolam Diam']);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['user_id' => $idle->id, 'type' => 'weekly']);
        Notification::assertNotSentTo($idle, WeeklyDigest::class);
    }

    public function test_digest_includes_active_outbreak_in_user_province(): void
    {
        $user = User::factory()->create(['province' => 'Jawa Barat']);
        OutbreakAlert::create([
            'disease_id' => Disease::factory()->create()->id,
            'province' => 'Jawa Barat',
            'cases_7d' => 9,
            'threshold' => 5,
            'message' => '9 kasus terlaporkan.',
            'status' => 'active',
        ]);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'weekly']);
        Notification::assertSentTo($user, WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->outbreaks === 1);
    }

    public function test_digest_respects_via_email_preference(): void
    {
        $user = User::factory()->create();
        FishScan::factory()->create(['user_id' => $user->id]);
        NotificationSetting::create([
            'user_id' => $user->id,
            'type' => 'weekly',
            'via_push' => true,
            'via_email' => false,
            'via_inapp' => true,
        ]);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'weekly']);
        Notification::assertNotSentTo($user, WeeklyDigest::class);
    }

    public function test_digest_respects_via_inapp_preference(): void
    {
        $user = User::factory()->create();
        FishScan::factory()->create(['user_id' => $user->id]);
        NotificationSetting::create([
            'user_id' => $user->id,
            'type' => 'weekly',
            'via_push' => true,
            'via_email' => true,
            'via_inapp' => false,
        ]);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id, 'type' => 'weekly']);
        Notification::assertSentTo($user, WeeklyDigest::class);
    }

    public function test_digest_skips_inactive_users(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        FishScan::factory()->create(['user_id' => $user->id]);

        $this->artisan('digest:weekly')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id, 'type' => 'weekly']);
        Notification::assertNotSentTo($user, WeeklyDigest::class);
    }

    public function test_weekly_type_is_listed_and_filterable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.6.type', 'weekly');

        $this->actingAs($user)->getJson('/api/notifications?type=weekly')->assertOk();

        $this->actingAs($user)->getJson('/api/notifications?type=bogus')->assertStatus(422);

        $this->actingAs($user)->putJson('/api/notification-settings', [
            'settings' => [['type' => 'weekly', 'via_email' => false]],
        ])->assertOk();

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'type' => 'weekly',
            'via_email' => false,
        ]);
    }

    public function test_weekly_digest_email_contains_unsubscribe_link(): void
    {
        $user = User::factory()->create();

        $html = (string) (new WeeklyDigest('22 Sep 2026 - 29 Sep 2026', 3, 2, 1))->toMail($user)->render();

        $this->assertStringContainsString('/unsubscribe?', $html);
        $this->assertStringContainsString('type=weekly', $html);
    }
}
