<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request): bool => $request->is('api/*');

        $envelope = fn (string $message, array $extra = []): array => array_merge([
            'success' => false,
            'message' => $message,
        ], $extra);

        $exceptions->shouldRenderJsonWhen($isApi);

        // 422 — validasi gagal (API.md §2.4)
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json($envelope('Validasi gagal', ['errors' => $e->errors()]), 422);
        });

        // 401 — token kosong / kedaluwarsa
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json($envelope('Tidak terautentikasi. Sertakan token yang valid.'), 401);
        });

        // 429 — rate limit (API.md §2.6) + retry_after
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                return null;
            }

            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return response()->json(
                $envelope("Terlalu banyak permintaan. Coba lagi dalam {$retryAfter} detik.", ['retry_after' => $retryAfter]),
                429
            );
        });

        // 413 — upload terlalu besar (FR-02.1.1)
        $exceptions->render(function (PostTooLargeException $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json(
                $envelope('File terlalu besar. Maksimum 10 MB per file.'),
                413
            );
        });

        // 404 / 403 / 419 / 5xx bawaan HTTP
        $exceptions->render(function (HttpException $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                // View spesifik bila ada, selain itu fallback default (perilaku lama untuk route web)
                $status = $e->getStatusCode();
                $view = 'errors.'.$status;
                if (! view()->exists($view)) {
                    $view = 'errors.default';
                }

                return response()->view($view, ['code' => $status], $status);
            }

            $status = $e->getStatusCode();
            $message = match ($status) {
                401 => 'Tidak terautentikasi. Sertakan token yang valid.',
                403 => 'Akses ditolak.',
                404 => 'Resource tidak ditemukan.',
                419 => 'Sesi kedaluwarsa. Muat ulang halaman.',
                429 => 'Terlalu banyak permintaan. Coba lagi dalam 60 detik.',
                503 => 'Layanan sedang tidak tersedia. Fitur scan dinonaktifkan sementara.',
                504 => 'Gateway timeout. Prediksi melewati batas waktu.',
                default => trim($e->getMessage()) !== '' && $status < 500
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server.',
            };

            $extra = [];
            if ($status === 429) {
                $extra['retry_after'] = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            }

            return response()->json($envelope($message, $extra), $status);
        });

        // Sisanya (500 tak terduga) — hanya untuk API
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request)) {
                return null;
            }

            // Response custom dari middleware (mis. rate limiter) biarkan framework yang render
            if ($e instanceof HttpResponseException) {
                return null;
            }

            report($e);

            return response()->json($envelope('Terjadi kesalahan pada server.'), 500);
        });
    })->create();
