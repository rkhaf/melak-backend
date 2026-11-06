<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTip;
use Illuminate\Http\JsonResponse;

/**
 * Tips budidaya harian (API.md §8.6, FR-06.5).
 */
class TipsController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/tips/today (publik) — konten dirotasi harian dari app_tips.
     */
    public function today(): JsonResponse
    {
        $tips = AppTip::where('is_active', true)->orderBy('id')->get();

        if ($tips->isEmpty()) {
            return $this->fail('Tips belum tersedia.', 404);
        }

        $today = now()->toDateString();

        $tip = $tips->first(fn (AppTip $tip) => $tip->published_on?->toDateString() === $today)
            ?? $tips->get((int) now()->format('z') % $tips->count());

        return $this->ok([
            'id' => $tip->id,
            'content' => $tip->content,
            'category' => $tip->category,
            'published_at' => ($tip->published_on ?? $tip->created_at)?->toIso8601String(),
        ]);
    }
}
