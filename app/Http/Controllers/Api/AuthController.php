<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification as InAppNotification;
use App\Models\NotificationSetting;
use App\Models\OauthAccount;
use App\Models\User;
use App\Notifications\NewDeviceLogin;
use App\Support\Notifier;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Endpoint autentikasi (API.md §3 / FR-01).
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/register (API.md §3.1, FR-01.1.1 — FR-01.1.4).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $this->generateUniqueUsername($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => null,
            'reputation_points' => 0,
            'reputation_level' => 'Pemula',
        ]);

        foreach (InAppNotification::TYPES as $type) {
            NotificationSetting::create([
                'user_id' => $user->id,
                'type' => $type,
                'via_push' => true,
                'via_email' => true,
                'via_inapp' => true,
            ]);
        }

        $user->notify(new VerifyEmail);

        return $this->created(
            $this->authPayload($user, $user->createToken('auth_token', ['*'], $this->tokenExpiry())->plainTextToken),
            'Registrasi berhasil. Email verifikasi telah dikirim.'
        );
    }

    /**
     * POST /api/login (API.md §3.3, FR-01.2.1 — FR-01.2.4).
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'sometimes|boolean',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->fail('Email atau kata sandi salah.', 401);
        }

        if (! $user->is_active) {
            return $this->fail('Akun Anda telah dinonaktifkan. Hubungi administrator.', 403);
        }

        $this->detectNewDevice($request, $user);

        $token = $user->createToken('auth_token', ['*'], $this->tokenExpiry())->plainTextToken;

        return $this->ok($this->authPayload($user, $token), 'Login berhasil');
    }

    /**
     * POST /api/auth/google (API.md §3.2, FR-01.1.2).
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string'], [
            'id_token.required' => 'id_token wajib diisi.',
        ]);

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->input('id_token'));
        } catch (\Exception $e) {
            return $this->fail('Token Google tidak valid atau sudah kedaluwarsa.', 401);
        }

        // Hardening: bila GOOGLE_CLIENT_ID di-set, token harus ditujukan untuk
        // klien kita (claim aud) — id_token dari aplikasi lain ditolak.
        $expectedAud = (string) config('services.google.client_id');
        if ($expectedAud !== '') {
            $aud = (string) ($googleUser->getRaw()['aud'] ?? '');
            if ($aud !== $expectedAud) {
                return $this->fail('Login Google gagal: token tidak ditujukan untuk aplikasi ini.', 401);
            }
        }

        $email = (string) $googleUser->getEmail();

        if ($email === '') {
            return $this->fail('Akun Google tidak menyediakan email.', 401);
        }

        $user = User::where('email', $email)->first();
        $googleVerified = (bool) ($googleUser->getRaw()['email_verified'] ?? false);

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@'),
                'email' => $email,
                'username' => $this->generateUniqueUsername($email),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $googleVerified ? now() : null,
                'role' => 'user',
                'is_active' => true,
                'password' => Hash::make(Str::random(40)),
                'reputation_points' => 0,
                'reputation_level' => 'Pemula',
            ]);

            foreach (InAppNotification::TYPES as $type) {
                NotificationSetting::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'via_push' => true,
                    'via_email' => true,
                    'via_inapp' => true,
                ]);
            }
        } elseif (! $user->is_active) {
            return $this->fail('Akun Anda telah dinonaktifkan. Hubungi administrator.', 403);
        }

        if (! $user->avatar_url && $googleUser->getAvatar()) {
            $user->forceFill(['avatar_url' => $googleUser->getAvatar()])->save();
        }

        OauthAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'google'],
            ['provider_id' => (string) $googleUser->getId()]
        );

        $this->detectNewDevice($request, $user);

        $token = $user->createToken('auth_token', ['*'], $this->tokenExpiry())->plainTextToken;

        return $this->ok($this->authPayload($user, $token), 'Login dengan Google berhasil');
    }

    /**
     * POST /api/logout (API.md §3.4, FR-01.2.3) — semua token akun di-revoke.
     */
    public function logout(Request $request): Response
    {
        $request->user()->tokens()->delete();
        Cache::forget('auth:device:'.$request->user()->id);

        return $this->noContent();
    }

    /**
     * POST /api/forgot-password (API.md §3.5, FR-01.3.1) — selalu 202 (anti-enumeration).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        Password::broker()->sendResetLink(['email' => $request->input('email')]);

        return $this->accepted(null, 'Jika email terdaftar, link reset password telah dikirim. Link berlaku 60 menit.');
    }

    /**
     * POST /api/reset-password (API.md §3.6, FR-01.3.1) — token one-time, lalu semua sesi direvoke.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token reset wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $credentials = [
            'email' => $this->emailFromResetToken($data['token']),
            'token' => $data['token'],
            'password' => $data['password'],
            'password_confirmation' => (string) $request->input('password_confirmation'),
        ];

        if (! $credentials['email']) {
            return $this->invalidResetToken();
        }

        $status = Password::broker()->reset($credentials, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
            Cache::forget('auth:device:'.$user->id);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->invalidResetToken();
        }

        return $this->ok(null, 'Kata sandi berhasil diatur ulang. Silakan login kembali.');
    }

    private function invalidResetToken(): JsonResponse
    {
        return $this->fail('Token reset tidak valid atau sudah kedaluwarsa.', 422, [
            'token' => ['Token reset tidak valid atau sudah kedaluwarsa.'],
        ]);
    }

    /**
     * Cari email pemilik token reset (API.md §3.6 hanya mengirim token, tanpa email).
     */
    private function emailFromResetToken(string $token): ?string
    {
        $rows = DB::table('password_reset_tokens')
            ->where('created_at', '>=', now()->subHour())
            ->get(['email', 'token']);

        foreach ($rows as $row) {
            if (Hash::check($token, $row->token)) {
                return $row->email;
            }
        }

        return null;
    }

    private function authPayload(User $user, string $token): array
    {
        return [
            'user' => $user->only([
                'id', 'name', 'username', 'email', 'role', 'avatar_url',
                'bio', 'province', 'reputation_points', 'reputation_level', 'email_verified_at',
            ]),
            'token' => $token,
            'token_expires_at' => $this->tokenExpiry()->toIso8601String(),
        ];
    }

    private function tokenExpiry(): \DateTimeInterface
    {
        $minutes = (int) config('sanctum.expiration', 43200);

        return now()->addMinutes($minutes);
    }

    private function detectNewDevice(Request $request, User $user): void
    {
        $device = md5((string) $request->ip().'|'.(string) $request->userAgent());
        $previous = Cache::get('auth:device:'.$user->id);

        Cache::put('auth:device:'.$user->id, $device, now()->addDays(30));

        if ($previous !== null && $previous !== $device) {
            Notifier::send(
                $user,
                'security',
                'Login dari perangkat baru',
                'Kami mendeteksi login ke akun Anda dari perangkat baru pada '.now()->format('d/m/Y H:i:s').'.',
                ['ip' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 180)],
                fn () => Notification::send($user, new NewDeviceLogin($request->ip(), (string) $request->userAgent(), now()))
            );
        }
    }

    private function generateUniqueUsername(string $email): string
    {
        $base = Str::of(Str::before($email, '@'))->lower()->replaceMatches('/[^a-z0-9._]/', '')->substr(0, 20);
        $base = $base ?: 'pengguna';

        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$counter;
            $counter++;
        }

        return $username;
    }
}
