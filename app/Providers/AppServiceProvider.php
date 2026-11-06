<?php

namespace App\Providers;

use App\Models\ForumComment;
use App\Models\ForumPost;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerAdminRateLimiter();
        $this->registerApiRateLimiters();
    }

    /**
     * Alias morph type singkat (reportable_type 'post'/'comment') ke model asli.
     */
    private function registerMorphMap(): void
    {
        Relation::morphMap([
            'post' => ForumPost::class,
            'comment' => ForumComment::class,
        ]);
    }

    /**
     * Rate limit login panel admin (respons HTML).
     */
    private function registerAdminRateLimiter(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower($request->input('login', $request->input('email', ''))))
                ->response(function () {
                    return back()->withErrors([
                        'login' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 1 menit.',
                    ]);
                });
        });
    }

    /**
     * Rate limit API mobile (API.md §2.6 / NFR-02.5) — respons JSON envelope.
     */
    private function registerApiRateLimiters(): void
    {
        // POST /api/login — 5 / menit / akun
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email', '')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(fn () => $this->throttleResponse(60));
        });

        // POST /api/register — 3 / jam / IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())->response(fn () => $this->throttleResponse(3600));
        });

        // POST /api/scans — 10 / jam / akun
        RateLimiter::for('scan', function (Request $request) {
            $user = $request->user('sanctum') ?? $request->user();

            return Limit::perHour(10)->by($user?->id ?: 'ip|'.$request->ip())
                ->response(fn () => $this->throttleResponse(3600));
        });

        // POST /api/forum/posts — 10 / jam / akun
        RateLimiter::for('forum-post', function (Request $request) {
            $user = $request->user('sanctum') ?? $request->user();

            return Limit::perHour(10)->by($user?->id ?: 'ip|'.$request->ip())
                ->response(fn () => $this->throttleResponse(3600));
        });
    }

    private function throttleResponse(int $retryAfter)
    {
        return response()->json([
            'success' => false,
            'message' => "Terlalu banyak permintaan. Coba lagi dalam {$retryAfter} detik.",
            'retry_after' => $retryAfter,
        ], 429);
    }
}
