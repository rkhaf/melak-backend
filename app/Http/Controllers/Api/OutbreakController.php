<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutbreakAlert;
use Illuminate\Http\JsonResponse;

/**
 * Alert wabah penyakit aktif (API.md §8.7, FR-06.4).
 */
class OutbreakController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/outbreaks [Auth] — dipoll klien (API.md §9.2).
     */
    public function index(): JsonResponse
    {
        $alerts = OutbreakAlert::active()
            ->with('disease:id,name,slug,severity')
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($alerts->map(fn (OutbreakAlert $alert) => [
            'id' => $alert->id,
            'disease' => $alert->disease ? [
                'id' => $alert->disease->id,
                'name' => $alert->disease->name,
                'slug' => $alert->disease->slug,
                'severity' => $alert->disease->severity,
            ] : null,
            'province' => $alert->province,
            'cases_7d' => (int) $alert->cases_7d,
            'threshold' => (int) $alert->threshold,
            'message' => $alert->message,
            'status' => $alert->status,
            'created_at' => $alert->created_at?->toIso8601String(),
        ])->values());
    }
}
