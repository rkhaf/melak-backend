<?php

namespace App\Http\Controllers\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Envelope response standar (API.md §2.4 / SRS §9.3):
 * { success, message, data } · terpaginasi menambahkan meta.
 */
trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'Berhasil', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function created(mixed $data = null, string $message = 'Berhasil'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function accepted(mixed $data = null, string $message = 'Berhasil'): JsonResponse
    {
        return $this->ok($data, $message, 202);
    }

    protected function paginated(LengthAwarePaginator $page, string $message = 'Berhasil', array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $page->items(),
            'meta' => array_merge([
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ], $extraMeta),
        ]);
    }

    protected function noContent(): Response
    {
        return response()->noContent();
    }

    protected function fail(string $message, int $status, array $errors = [], array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $errors !== [] ? ['errors' => $errors] : [], $extra), $status);
    }
}
