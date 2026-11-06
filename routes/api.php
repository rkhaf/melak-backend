<?php

use App\Http\Controllers\Api\AppInfoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\ForumCommentController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\HelpController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationSettingsController;
use App\Http\Controllers\Api\OutbreakController;
use App\Http\Controllers\Api\PondController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\TipsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (kontrak: API.md)
|--------------------------------------------------------------------------
*/

// §3 — Autentikasi
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// §4 — Scan & prediksi
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:scan');
    Route::get('/scans', [ScanController::class, 'index']);
    Route::get('/scans/export', [ScanController::class, 'export']);
    Route::get('/scans/{id}/export-pdf', [ScanController::class, 'exportPdf']);
    Route::get('/scans/{id}', [ScanController::class, 'show']);
    Route::delete('/scans/{id}', [ScanController::class, 'destroy']);
});

// §5 — Ensiklopedia penyakit (publik)
Route::get('/diseases', [DiseaseController::class, 'index']);
Route::get('/diseases/search', [DiseaseController::class, 'search']);
Route::get('/diseases/{slug}/similar', [DiseaseController::class, 'similar']);
Route::get('/diseases/{slug}', [DiseaseController::class, 'show']);

// §6 — Forum komunitas
Route::get('/forum/categories', [ForumController::class, 'categories']);
Route::get('/forum/posts', [ForumController::class, 'index']);
Route::get('/forum/posts/{id}', [ForumController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/forum/posts', [ForumController::class, 'store'])->middleware('throttle:forum-post');
    Route::put('/forum/posts/{id}', [ForumController::class, 'update']);
    Route::delete('/forum/posts/{id}', [ForumController::class, 'destroy']);
    Route::post('/forum/posts/{id}/vote', [ForumController::class, 'vote']);
    Route::post('/forum/posts/{id}/bookmark', [ForumController::class, 'bookmark']);
    Route::post('/forum/posts/{id}/follow', [ForumController::class, 'follow']);
    Route::post('/forum/posts/{id}/report', [ForumController::class, 'report']);

    Route::post('/forum/posts/{id}/comments', [ForumCommentController::class, 'store']);
    Route::put('/forum/comments/{id}', [ForumCommentController::class, 'update']);
    Route::delete('/forum/comments/{id}', [ForumCommentController::class, 'destroy']);
    Route::post('/forum/comments/{id}/vote', [ForumCommentController::class, 'vote']);
    Route::post('/forum/comments/{id}/best', [ForumCommentController::class, 'markBest']);
});

// §7 — Dashboard, kolam, profil & peta
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, '__invoke']);

    Route::get('/ponds', [PondController::class, 'index']);
    Route::get('/ponds/{id}', [PondController::class, 'show']);
    Route::post('/ponds', [PondController::class, 'store']);
    Route::put('/ponds/{id}', [PondController::class, 'update']);
    Route::delete('/ponds/{id}', [PondController::class, 'destroy']);
    Route::get('/ponds/{id}/health-report', [PondController::class, 'healthReport']);

    Route::get('/user', [ProfileController::class, 'me']);
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'changePassword']);
    Route::post('/user/avatar', [ProfileController::class, 'uploadAvatar']);
});

Route::get('/users/{id}', [ProfileController::class, 'show']);
Route::get('/map/heatmap', [MapController::class, 'heatmap']);
Route::get('/map/pins', [MapController::class, 'pins']);

// §8 — Notifikasi, tips & outbreak
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::get('/notification-settings', [NotificationSettingsController::class, 'index']);
    Route::put('/notification-settings', [NotificationSettingsController::class, 'update']);
    Route::get('/outbreaks', [OutbreakController::class, 'index']);
});

Route::get('/tips/today', [TipsController::class, 'today']);

// Publik (bukan bagian tabel endpoint utama)
Route::get('/help', [HelpController::class, 'index']);
Route::get('/app-info', [AppInfoController::class, 'index']);
