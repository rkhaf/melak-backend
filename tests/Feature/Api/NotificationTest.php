<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function makeNotification(User $user, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'scan_result',
            'title' => 'Hasil scan siap',
            'body' => 'White Spot (Ich) terdeteksi dengan keyakinan 87%',
            'data' => ['scan_id' => '6f9b2c1e-0000'],
            'read_at' => null,
        ], $overrides));
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401)->assertJsonPath('success', false);
        $this->getJson('/api/notification-settings')->assertStatus(401);
        $this->putJson('/api/notification-settings', [])->assertStatus(401);
        $this->getJson('/api/outbreaks')->assertStatus(401);
    }

    public function test_notification_list_with_filters_and_meta(): void
    {
        $user = $this->user();
        $other = $this->user();

        $this->makeNotification($user);
        $this->makeNotification($user, ['type' => 'outbreak', 'title' => 'Wabah', 'read_at' => now()]);
        $this->makeNotification($user, ['type' => 'security', 'title' => 'Login baru']);
        $this->makeNotification($other, ['title' => 'Milik orang lain']);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonStructure(['data' => [['id', 'type', 'title', 'body', 'data', 'read_at', 'created_at']]]);

        $this->actingAs($user)->getJson('/api/notifications?type=outbreak')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Wabah');

        $this->actingAs($user)->getJson('/api/notifications?unread_only=true')
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($user)->getJson('/api/notifications?type=invalid')->assertStatus(422);
    }

    public function test_mark_read_and_mark_all_read(): void
    {
        $user = $this->user();
        $first = $this->makeNotification($user);
        $this->makeNotification($user, ['type' => 'tips']);

        $stranger = $this->user();
        $strangerNote = $this->makeNotification($stranger);

        $this->actingAs($user)->patchJson("/api/notifications/{$strangerNote->id}/read")
            ->assertStatus(404);

        $this->actingAs($user)->patchJson("/api/notifications/{$first->id}/read")
            ->assertOk()
            ->assertJsonPath('data.type', 'scan_result')
            ->assertJsonPath('data.id', $first->id);

        $this->assertNotNull($first->fresh()->read_at);

        $this->actingAs($user)->getJson('/api/notifications?unread_only=true')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($user)->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->actingAs($user)->getJson('/api/notifications')
            ->assertJsonPath('meta.unread_count', 0);

        $this->actingAs($user)->patchJson('/api/notifications/999999/read')->assertStatus(404);
    }

    public function test_notification_settings_defaults_and_update(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->getJson('/api/notification-settings');

        $response->assertOk()->assertJsonCount(7, 'data');
        $this->assertSame(
            ['scan_result', 'forum_reply', 'mention', 'outbreak', 'tips', 'security', 'weekly'],
            array_column($response->json('data'), 'type')
        );
        $this->assertTrue($response->json('data.0.via_inapp'));
        $this->assertSame(7, NotificationSetting::where('user_id', $user->id)->count());

        $this->actingAs($user)->putJson('/api/notification-settings', [
            'settings' => [
                ['type' => 'scan_result', 'via_push' => true, 'via_email' => false, 'via_inapp' => true],
                ['type' => 'tips', 'via_push' => false, 'via_email' => false, 'via_inapp' => false],
            ],
        ])
            ->assertOk()
            ->assertJsonCount(7, 'data');

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'type' => 'scan_result',
            'via_email' => 0,
        ]);
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'type' => 'tips',
            'via_inapp' => 0,
        ]);

        $this->actingAs($user)->putJson('/api/notification-settings', [
            'settings' => [['type' => 'bogus']],
        ])->assertStatus(422);

        $this->actingAs($user)->putJson('/api/notification-settings', [
            'settings' => [
                ['type' => 'tips'],
                ['type' => 'tips'],
            ],
        ])->assertStatus(422);
    }

    public function test_inapp_notification_respects_via_inapp_preference(): void
    {
        $user = $this->user();
        NotificationSetting::create([
            'user_id' => $user->id,
            'type' => 'tips',
            'via_push' => true,
            'via_email' => false,
            'via_inapp' => false,
        ]);

        Notifier::send($user, 'tips', 'Tips hari ini', 'Isi tips', ['id' => 1]);

        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id, 'type' => 'tips']);

        NotificationSetting::where('user_id', $user->id)->where('type', 'tips')->update(['via_inapp' => true]);

        Notifier::send($user, 'tips', 'Tips hari ini', 'Isi tips', ['id' => 2]);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'tips', 'title' => 'Tips hari ini']);
    }

    public function test_email_failure_does_not_break_notification(): void
    {
        $user = $this->user();

        Log::spy();

        // Meniru kegagalan SMTP di production (host mail tidak ter-resolve) —
        // tidak boleh ada exception yang bocor ke pemanggil.
        Notifier::send(
            $user,
            'scan_result',
            'Hasil scan siap',
            'Prediksi: White Spot (Ich) 87%.',
            ['scan_id' => 1],
            fn () => throw new \RuntimeException('Connection could not be established with host "mailpit:1025"'),
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'scan_result',
            'title' => 'Hasil scan siap',
        ]);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => $message === 'Notifikasi email gagal dikirim'
                && $context['user_id'] === $user->id)
            ->once();
    }
}
