<?php

use App\Http\Controllers\Admin\AppInfoController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiseaseAdminController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ForumModerationController;
use App\Http\Controllers\Admin\HelpContactController;
use App\Http\Controllers\Admin\ModelAdminController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingAdminController;
use App\Http\Controllers\Admin\TipAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ScanWebController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\AppInfo;
use App\Models\Faq;
use App\Models\HelpContact;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Landing page publik (halaman utama domain).
Route::get('/', function () {
    $appInfo = AppInfo::first();

    return view('landing', [
        'appName' => $appInfo->app_name ?? 'Melak',
        'apkUrl' => $appInfo?->apk_path,
        'apkVersion' => $appInfo->apk_version ?? '1.0.0',
        'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->get(),
        'contacts' => HelpContact::where('is_active', true)->orderBy('sort_order')->get(),
    ]);
})->name('landing');

// Verifikasi email pengguna aplikasi (FR-01.1.3)
Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::find($id);

    if (! $user || ! hash_equals(sha1($user->email), $hash)) {
        abort(403, 'Link verifikasi tidak valid.');
    }

    if (! $user->email_verified_at) {
        $user->forceFill(['email_verified_at' => now()])->save();
    }

    return view('auth.verify-email', ['user' => $user]);
})->middleware('signed')->name('verification.verify');

// Halaman reset password dari link email (FR-01.3.1)
Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');

// Hasil scan yang dibuka dari link email notifikasi (FR-06.1). Link bertanda
// tangan — jangan diarahkan ke /api/scans/{id} yang membalas JSON (401 tanpa
// token), karena pembaca email bukan klien API.
Route::get('/scans/{id}', [ScanWebController::class, 'show'])
    ->middleware('signed')
    ->name('scans.show');

// Unsubscribe email notifikasi per tipe — link bertanda tangan di tiap
// email notifikasi (FR-06.2). Menonaktifkan via_email untuk tipe tsb.
Route::get('/unsubscribe', function (Request $request) {
    $data = $request->validate([
        'user' => 'required|uuid',
        'type' => 'required|string|in:'.implode(',', Notification::TYPES),
    ]);

    $user = User::find($data['user']);

    abort_unless($user, 404);

    NotificationSetting::updateOrCreate(
        ['user_id' => $user->id, 'type' => $data['type']],
        ['via_email' => false]
    );

    return view('auth.unsubscribe', [
        'type' => $data['type'],
        'email' => $user->email,
    ]);
})->middleware(['signed', 'throttle:30,1'])->name('unsubscribe');

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post')->middleware('throttle:admin-login');

Route::get('/admin/forgot-password', [AdminAuthController::class, 'showForgotPasswordForm'])->name('admin.forgot-password');
Route::post('/admin/forgot-password', [AdminAuthController::class, 'sendResetOtp'])->name('admin.forgot-password.post')->middleware('throttle:admin-login');

Route::get('/admin/forgot-password/otp', [AdminAuthController::class, 'showOtpForm'])->name('admin.forgot-password.otp');
Route::post('/admin/forgot-password/otp', [AdminAuthController::class, 'verifyOtp'])->name('admin.forgot-password.otp.post')->middleware('throttle:admin-login');

Route::get('/admin/reset-password', [AdminAuthController::class, 'showResetPasswordForm'])->name('admin.reset-password');
Route::post('/admin/reset-password', [AdminAuthController::class, 'resetPassword'])->name('admin.reset-password.post')->middleware('throttle:admin-login');

// Protected Admin Routes
Route::middleware([AdminMiddleware::class])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard/export', [DashboardController::class, 'export'])->name('admin.dashboard.export');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Moderasi Forum (FR-05.5.2 — FR-05.5.4)
    Route::get('/admin/forum', [ForumModerationController::class, 'index'])->name('admin.forum.index');
    Route::post('/admin/forum/{post}/approve', [ForumModerationController::class, 'approve'])->name('admin.forum.approve');
    Route::post('/admin/forum/{post}/reject', [ForumModerationController::class, 'reject'])->name('admin.forum.reject');
    Route::post('/admin/forum/{post}/pin', [ForumModerationController::class, 'pin'])->name('admin.forum.pin');
    Route::post('/admin/forum/{post}/lock', [ForumModerationController::class, 'lock'])->name('admin.forum.lock');
    Route::delete('/admin/forum/{post}', [ForumModerationController::class, 'destroy'])->name('admin.forum.destroy');

    // Laporan Forum (FR-09.1.2 / NFR-02.7)
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::post('/admin/reports/{report}/review', [ReportController::class, 'review'])->name('admin.reports.review');
    Route::post('/admin/reports/{report}/dismiss', [ReportController::class, 'dismiss'])->name('admin.reports.dismiss');

    // Data Penyakit (FR-09.2.1)
    Route::get('/admin/diseases', [DiseaseAdminController::class, 'index'])->name('admin.diseases.index');
    Route::get('/admin/diseases/create', [DiseaseAdminController::class, 'create'])->name('admin.diseases.create');
    Route::post('/admin/diseases', [DiseaseAdminController::class, 'store'])->name('admin.diseases.store');
    Route::get('/admin/diseases/{disease}/edit', [DiseaseAdminController::class, 'edit'])->name('admin.diseases.edit');
    Route::put('/admin/diseases/{disease}', [DiseaseAdminController::class, 'update'])->name('admin.diseases.update');
    Route::delete('/admin/diseases/{disease}', [DiseaseAdminController::class, 'destroy'])->name('admin.diseases.destroy');

    // Model ML & Dataset (FR-09.2.2 — FR-09.2.3)
    Route::get('/admin/models', [ModelAdminController::class, 'index'])->name('admin.models.index');
    Route::post('/admin/models', [ModelAdminController::class, 'store'])->name('admin.models.store');
    Route::post('/admin/models/rollback', [ModelAdminController::class, 'rollback'])->name('admin.models.rollback');
    Route::post('/admin/models/test', [ModelAdminController::class, 'test'])->name('admin.models.test');
    Route::get('/admin/models/status', [ModelAdminController::class, 'status'])->name('admin.models.status');
    Route::post('/admin/models/{model}/activate', [ModelAdminController::class, 'activate'])->name('admin.models.activate');
    Route::delete('/admin/models/{model}', [ModelAdminController::class, 'destroy'])->name('admin.models.destroy');
    Route::post('/admin/datasets', [ModelAdminController::class, 'storeDataset'])->name('admin.datasets.store');
    Route::delete('/admin/datasets/{dataset}', [ModelAdminController::class, 'destroyDataset'])->name('admin.datasets.destroy');

    // Tips Harian (FR-06.5)
    Route::get('/admin/tips', [TipAdminController::class, 'index'])->name('admin.tips.index');
    Route::get('/admin/tips/create', [TipAdminController::class, 'create'])->name('admin.tips.create');
    Route::post('/admin/tips', [TipAdminController::class, 'store'])->name('admin.tips.store');
    Route::get('/admin/tips/{tip}/edit', [TipAdminController::class, 'edit'])->name('admin.tips.edit');
    Route::put('/admin/tips/{tip}', [TipAdminController::class, 'update'])->name('admin.tips.update');
    Route::delete('/admin/tips/{tip}', [TipAdminController::class, 'destroy'])->name('admin.tips.destroy');
    Route::post('/admin/tips/{tip}/toggle', [TipAdminController::class, 'toggle'])->name('admin.tips.toggle');

    // Pengaturan Aplikasi (FR-09)
    Route::get('/admin/settings', [SettingAdminController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [SettingAdminController::class, 'update'])->name('admin.settings.update');

    // Master Data: Kontak Penting
    Route::get('/admin/contacts', [HelpContactController::class, 'index'])->name('admin.contacts.index');
    Route::get('/admin/contacts/create', [HelpContactController::class, 'create'])->name('admin.contacts.create');
    Route::post('/admin/contacts', [HelpContactController::class, 'store'])->name('admin.contacts.store');
    Route::get('/admin/contacts/{contact}/edit', [HelpContactController::class, 'edit'])->name('admin.contacts.edit');
    Route::put('/admin/contacts/{contact}', [HelpContactController::class, 'update'])->name('admin.contacts.update');
    Route::delete('/admin/contacts/{contact}', [HelpContactController::class, 'destroy'])->name('admin.contacts.destroy');
    Route::post('/admin/contacts/reorder', [HelpContactController::class, 'reorder'])->name('admin.contacts.reorder');
    Route::post('/admin/contacts/{contact}/toggle', [HelpContactController::class, 'toggle'])->name('admin.contacts.toggle');

    // Master Data: FAQ
    Route::get('/admin/faqs', [FaqController::class, 'index'])->name('admin.faqs.index');
    Route::get('/admin/faqs/create', [FaqController::class, 'create'])->name('admin.faqs.create');
    Route::post('/admin/faqs', [FaqController::class, 'store'])->name('admin.faqs.store');
    Route::get('/admin/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('admin.faqs.edit');
    Route::put('/admin/faqs/{faq}', [FaqController::class, 'update'])->name('admin.faqs.update');
    Route::delete('/admin/faqs/{faq}', [FaqController::class, 'destroy'])->name('admin.faqs.destroy');
    Route::post('/admin/faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('admin.faqs.toggle');

    // Konfigurasi Aplikasi (single row)
    Route::get('/admin/app-info', [AppInfoController::class, 'edit'])->name('admin.app-info.edit');
    Route::put('/admin/app-info', [AppInfoController::class, 'update'])->name('admin.app-info.update');
    Route::post('/admin/app-info/apk', [AppInfoController::class, 'uploadApk'])->name('admin.app-info.apk.upload');
    Route::post('/admin/app-info/apk/remove', [AppInfoController::class, 'removeApk'])->name('admin.app-info.apk.remove');

    // Manajemen Pengguna
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{user}/toggle', [UserController::class, 'toggle'])->name('admin.users.toggle');
    Route::post('/admin/users/{user}/ban', [UserController::class, 'ban'])->name('admin.users.ban');
    Route::post('/admin/users/{user}/unban', [UserController::class, 'unban'])->name('admin.users.unban');
    Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::post('/admin/users/{user}/notifications', [UserController::class, 'updateNotifications'])->name('admin.users.notifications');

    // Profil Admin
    Route::get('/admin/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/admin/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::put('/admin/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

    // Audit Log
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
});
