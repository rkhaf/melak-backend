<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminOtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthTest extends AdminTestCase
{
    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Masuk');
    }

    public function test_authenticated_admin_is_redirected_from_login_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_login_with_email(): void
    {
        $admin = $this->createAdmin(['email' => 'admin@ngingu.test', 'password' => 'secret123']);

        $this->post(route('admin.login.post'), [
            'login' => 'admin@ngingu.test',
            'password' => 'secret123',
        ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_login_with_username(): void
    {
        $this->createAdmin(['username' => 'adminngingu', 'password' => 'secret123']);

        $this->post(route('admin.login.post'), [
            'login' => 'adminngingu',
            'password' => 'secret123',
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->createAdmin(['email' => 'admin@ngingu.test', 'password' => 'secret123']);

        $this->from(route('admin.login'))
            ->post(route('admin.login.post'), [
                'login' => 'admin@ngingu.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_rejects_regular_user_role(): void
    {
        $this->createRegularUser(['email' => 'warga@ngingu.test', 'password' => 'secret123']);

        $this->from(route('admin.login'))
            ->post(route('admin.login.post'), [
                'login' => 'warga@ngingu.test',
                'password' => 'secret123',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_requires_login_and_password(): void
    {
        $this->post(route('admin.login.post'), [])
            ->assertSessionHasErrors(['login', 'password']);
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_guest_can_request_password_reset_otp(): void
    {
        Mail::fake();
        $this->createAdmin(['email' => 'admin@ngingu.test']);

        $this->from(route('admin.forgot-password'))
            ->post(route('admin.forgot-password.post'), [
                'email' => 'admin@ngingu.test',
            ])
            ->assertRedirect(route('admin.forgot-password.otp', ['email' => 'admin@ngingu.test']))
            ->assertSessionHas('info');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'admin@ngingu.test']);

        Mail::assertSent(AdminOtpMail::class, function (AdminOtpMail $mail) {
            return $mail->hasTo('admin@ngingu.test');
        });
    }

    public function test_forgot_password_rejects_unregistered_email(): void
    {
        $this->from(route('admin.forgot-password'))
            ->post(route('admin.forgot-password.post'), [
                'email' => 'unknown@ngingu.test',
            ])
            ->assertRedirect(route('admin.forgot-password'))
            ->assertSessionHasErrors('email');
    }

    public function test_forgot_password_rejects_non_admin_email(): void
    {
        $this->createRegularUser(['email' => 'warga@ngingu.test']);

        $this->from(route('admin.forgot-password'))
            ->post(route('admin.forgot-password.post'), [
                'email' => 'warga@ngingu.test',
            ])
            ->assertRedirect(route('admin.forgot-password'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_reset_password_using_otp(): void
    {
        Mail::fake();
        $admin = $this->createAdmin(['email' => 'admin@ngingu.test', 'password' => 'old-password']);

        $otp = null;
        Mail::assertNothingSent();

        $this->post(route('admin.forgot-password.post'), ['email' => 'admin@ngingu.test']);

        Mail::assertSent(AdminOtpMail::class, function (AdminOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->assertNotNull($otp, 'OTP should be captured from the sent mail.');

        $this->from(route('admin.forgot-password.otp', ['email' => 'admin@ngingu.test']))
            ->post(route('admin.forgot-password.otp.post'), [
                'email' => 'admin@ngingu.test',
                'otp' => $otp,
            ])
            ->assertRedirect();

        $this->post(route('admin.reset-password.post'), [
            'email' => 'admin@ngingu.test',
            'token' => $this->resetTokenFor('admin@ngingu.test'),
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'admin@ngingu.test']);

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));

        $this->post(route('admin.login.post'), [
            'login' => 'admin@ngingu.test',
            'password' => 'new-password-123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_verify_otp_fails_with_wrong_code(): void
    {
        $this->createAdmin(['email' => 'admin@ngingu.test']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@ngingu.test',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->from(route('admin.forgot-password.otp', ['email' => 'admin@ngingu.test']))
            ->post(route('admin.forgot-password.otp.post'), [
                'email' => 'admin@ngingu.test',
                'otp' => '000000',
            ])
            ->assertSessionHasErrors('otp');
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        $this->createAdmin(['email' => 'admin@ngingu.test']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@ngingu.test',
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->from(route('admin.forgot-password.otp', ['email' => 'admin@ngingu.test']))
            ->post(route('admin.forgot-password.otp.post'), [
                'email' => 'admin@ngingu.test',
                'otp' => '123456',
            ])
            ->assertSessionHasErrors('otp');
    }

    public function test_reset_password_requires_confirmed_password_min_8(): void
    {
        $this->from(route('admin.reset-password'))
            ->post(route('admin.reset-password.post'), [
                'email' => 'admin@ngingu.test',
                'token' => 'abc',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $this->createAdmin(['email' => 'admin@ngingu.test']);

        $this->from(route('admin.reset-password'))
            ->post(route('admin.reset-password.post'), [
                'email' => 'admin@ngingu.test',
                'token' => 'tampered-token',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasErrors('email');
    }

    private function resetTokenFor(string $email): string
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        return hash_hmac('sha256', $email.'|'.$record->token, (string) config('app.key'));
    }
}
