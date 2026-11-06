<?php

namespace Tests\Feature;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * FR-06.2 — link unsubscribe bertanda tangan di tiap email notifikasi.
 */
class UnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_unsubscribe_disables_email_for_that_type_only(): void
    {
        $user = User::factory()->create();
        NotificationSetting::create([
            'user_id' => $user->id,
            'type' => 'weekly',
            'via_push' => true,
            'via_email' => true,
            'via_inapp' => true,
        ]);

        $url = URL::signedRoute('unsubscribe', ['user' => $user->id, 'type' => 'weekly']);

        $this->get($url)
            ->assertOk()
            ->assertSee('Email Dinonaktifkan')
            ->assertSee($user->email);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'type' => 'weekly',
            'via_email' => false,
        ]);
        $this->assertDatabaseMissing('notification_settings', [
            'user_id' => $user->id,
            'type' => 'scan_result',
        ]);

        $row = NotificationSetting::where('user_id', $user->id)->where('type', 'weekly')->first();
        $this->assertTrue((bool) $row->via_inapp);
        $this->assertTrue((bool) $row->via_push);
    }

    public function test_unsubscribe_without_signature_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->get('/unsubscribe?user='.$user->id.'&type=weekly')->assertForbidden();

        $this->assertDatabaseMissing('notification_settings', ['user_id' => $user->id, 'type' => 'weekly']);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $url = URL::signedRoute('unsubscribe', ['user' => $victim->id, 'type' => 'weekly']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $params['user'] = $attacker->id;

        $this->get('/unsubscribe?'.http_build_query($params))->assertForbidden();

        $this->assertDatabaseMissing('notification_settings', ['user_id' => $attacker->id, 'type' => 'weekly']);
    }

    public function test_invalid_type_is_rejected_even_with_valid_signature(): void
    {
        $user = User::factory()->create();
        $url = URL::signedRoute('unsubscribe', ['user' => $user->id, 'type' => 'not_a_type']);

        $this->get($url)
            ->assertStatus(302)
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('notification_settings', ['user_id' => $user->id]);
    }

    public function test_unknown_user_returns_404(): void
    {
        $url = URL::signedRoute('unsubscribe', ['user' => (string) Str::uuid(), 'type' => 'weekly']);

        $this->get($url)->assertNotFound();
    }
}
