<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_register_returns_201_with_token_and_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.user.email_verified_at', null)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name'], 'token', 'token_expires_at']]);

        $this->assertNotNull($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => User::where('email', 'budi@example.com')->first()->id,
            'type' => 'scan_result',
        ]);

        Notification::assertSentTo(User::where('email', 'budi@example.com')->first(), VerifyEmail::class);
    }

    public function test_register_validates_unique_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $this->postJson('/api/register', $this->validPayload(['email' => 'dupe@example.com']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);

        $this->postJson('/api/register', $this->validPayload(['password_confirmation' => 'beda']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => bcrypt('rahasia123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'remember_me' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'role'], 'token', 'token_expires_at']]);

        $this->assertNotNull($user->tokens()->first());
    }

    public function test_login_fails_with_wrong_credentials_401(): void
    {
        User::factory()->create(['email' => 'budi@example.com', 'password' => bcrypt('rahasia123')]);

        $this->postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_login_blocked_for_inactive_account(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'password' => bcrypt('rahasia123'),
            'is_active' => false,
        ]);

        $this->postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'rahasia123'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_login_is_rate_limited_to_five_per_minute(): void
    {
        User::factory()->create(['email' => 'budi@example.com', 'password' => bcrypt('rahasia123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'salah'])
                ->assertStatus(401);
        }

        $last = $this->postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'salah']);
        $last->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_logout_revokes_all_tokens(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_new_device_login_sends_security_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => bcrypt('rahasia123')]);

        Cache::put('auth:device:'.$user->id, md5('1.1.1.1|other-agent'), now()->addDay());

        $this->withHeader('User-Agent', 'Pixel-9-Pro')->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->assertOk();

        Notification::assertSentTo($user, NewDeviceLogin::class);
    }

    public function test_forgot_password_always_returns_202(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'ada@example.com'])
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'budi@example.com'])
            ->assertStatus(202);
    }

    public function test_reset_password_with_valid_token_updates_password_and_revokes_sessions(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => bcrypt('rahasia123')]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'password' => 'baruanjur99',
            'password_confirmation' => 'baruanjur99',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('baruanjur99', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $this->postJson('/api/reset-password', [
            'token' => 'token-ngawur',
            'password' => 'baruanjur99',
            'password_confirmation' => 'baruanjur99',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['token']]);
    }

    public function test_google_login_rejects_invalid_id_token(): void
    {
        Socialite::shouldReceive('driver')
            ->once()->with('google')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('userFromToken')
            ->andThrow(new \Exception('Invalid ID token'));

        $this->postJson('/api/auth/google', ['id_token' => 'token-palsu'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_google_login_rejects_token_with_wrong_audience(): void
    {
        config(['services.google.client_id' => 'klien-kita.apps.googleusercontent.com']);

        $googleUser = new class
        {
            public function getRaw(): array
            {
                return ['aud' => 'klien-lain.apps.googleusercontent.com', 'email_verified' => true];
            }
        };

        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('userFromToken')->andReturn($googleUser);

        // aud tidak cocok dengan GOOGLE_CLIENT_ID → token sah tapi dari
        // aplikasi lain, wajib ditolak (bukan dipakai untuk login).
        $this->postJson('/api/auth/google', ['id_token' => 'token-sah-aud-lain'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
