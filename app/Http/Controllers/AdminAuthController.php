<?php

namespace App\Http\Controllers;

use App\Mail\AdminOtpMail;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthController extends Controller
{
    /**
     * OTP validity duration in minutes.
     */
    private const OTP_TTL_MINUTES = 15;

    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    /**
     * Handle admin login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email atau username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginField = $request->input('login');
        $password = $request->input('password');

        // Check if input is email or username
        $loginType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginType, $loginField)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors([
                'login' => 'Kredensial yang Anda masukkan salah.',
            ])->withInput($request->only('login', 'remember'));
        }

        // Verify role
        if (! in_array($user->role, ['admin', 'superadmin'])) {
            return back()->withErrors([
                'login' => 'Akses ditolak. Anda tidak memiliki hak akses Administrator.',
            ])->withInput($request->only('login', 'remember'));
        }

        // Login user
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Audit::log('auth.login', 'Administrator "'.$user->name.'" berhasil masuk.');

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Audit::log('auth.logout', 'Administrator "'.(Auth::user()?->name ?? '?').'" keluar.');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPasswordForm()
    {
        return view('admin.forgot-password');
    }

    /**
     * Send OTP reset code (delivered via email; falls back to simulation for dev).
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Alamat email tidak terdaftar di sistem.',
        ]);

        $email = $request->input('email');

        // Hanya akun administrator yang boleh mereset kata sandi via jalur ini.
        $user = User::where('email', $email)->first();
        if (! $user || ! in_array($user->role, ['admin', 'superadmin'])) {
            return back()->withErrors([
                'email' => 'Alamat email bukan akun administrator.',
            ])->withInput($request->only('email'));
        }

        $otp = sprintf('%06d', mt_rand(100000, 999999));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $delivered = false;

        try {
            Mail::to($email)->send(new AdminOtpMail($otp, self::OTP_TTL_MINUTES.' menit'));
            $delivered = true;
        } catch (\Throwable $e) {
            report($e);
        }

        Audit::log('auth.otp_requested', 'Permintaan reset kata sandi untuk "'.$email.'" (terkirim via email: '.($delivered ? 'ya' : 'tidak').').');

        return redirect()->route('admin.forgot-password.otp', ['email' => $email])
            ->with('simulated_otp', $delivered ? null : $otp)
            ->with('info', $delivered
                ? 'Kode OTP telah dikirim ke email Anda.'
                : 'Mode simulasi: email belum dikonfigurasi. Gunakan kode OTP yang ditampilkan untuk menguji alur reset.');
    }

    /**
     * Show OTP verification form.
     */
    public function showOtpForm(Request $request)
    {
        $email = $request->query('email');
        if (! $email) {
            return redirect()->route('admin.forgot-password');
        }

        return view('admin.forgot-password-otp', compact('email'));
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus 6 digit.',
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($otp, $record->token)) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.']);
        }

        if ($this->otpIsExpired($record)) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.']);
        }

        // OTP verified, redirect to password reset form with hashed email/token to proceed safely
        return redirect()->route('admin.reset-password', ['email' => $email, 'token' => $this->resetToken($email, $record)]);
    }

    /**
     * Show Reset Password form.
     */
    public function showResetPasswordForm(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (! $email || ! $token) {
            return redirect()->route('admin.forgot-password');
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $record || $this->resetToken($email, $record) !== $token || $this->otpIsExpired($record)) {
            return redirect()->route('admin.forgot-password')->withErrors(['email' => 'Sesi pemulihan tidak valid atau sudah kedaluwarsa.']);
        }

        return view('admin.reset-password', compact('email', 'token'));
    }

    /**
     * Reset the password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $email = $request->input('email');
        $token = $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $record || $this->resetToken($email, $record) !== $token || $this->otpIsExpired($record)) {
            return redirect()->route('admin.forgot-password')->withErrors(['email' => 'Sesi pemulihan tidak valid atau sudah kedaluwarsa.']);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($request->input('password'));
            $user->save();
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        Audit::log('auth.password_reset', 'Kata sandi untuk "'.$email.'" berhasil direset.');

        return redirect()->route('admin.login')->with('success', 'Kata sandi berhasil diubah. Silakan masuk kembali.');
    }

    /**
     * Check whether the stored OTP has expired.
     */
    private function otpIsExpired(object $record): bool
    {
        $createdAt = $record->created_at
            ? Carbon::parse($record->created_at)
            : null;

        return ! $createdAt || $createdAt->addMinutes(self::OTP_TTL_MINUTES)->isPast();
    }

    /**
     * Build the tamper-proof reset token.
     */
    private function resetToken(string $email, object $record): string
    {
        return hash_hmac('sha256', $email.'|'.$record->token, (string) config('app.key'));
    }
}
