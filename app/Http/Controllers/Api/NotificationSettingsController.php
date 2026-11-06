<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Preferensi kanal notifikasi (API.md §8.4 — §8.5, FR-06.6).
 */
class NotificationSettingsController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/notification-settings (API.md §8.4).
     */
    public function index(Request $request): JsonResponse
    {
        return $this->ok($this->rows($request->user()->id));
    }

    /**
     * PUT /api/notification-settings (API.md §8.5).
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array|min:1|max:7',
            'settings.*.type' => 'required|string|in:scan_result,forum_reply,mention,outbreak,tips,security,weekly',
            'settings.*.via_push' => 'sometimes|boolean',
            'settings.*.via_email' => 'sometimes|boolean',
            'settings.*.via_inapp' => 'sometimes|boolean',
        ], [
            'settings.required' => 'Daftar pengaturan wajib diisi.',
            'settings.*.type.in' => 'Tipe notifikasi tidak valid.',
        ]);

        $types = array_column($data['settings'], 'type');

        if (count($types) !== count(array_unique($types))) {
            return $this->fail('Tipe notifikasi tidak boleh ganda.', 422);
        }

        foreach ($data['settings'] as $setting) {
            NotificationSetting::updateOrCreate(
                ['user_id' => $request->user()->id, 'type' => $setting['type']],
                array_intersect_key($setting, array_flip(['via_push', 'via_email', 'via_inapp']))
            );
        }

        return $this->ok($this->rows($request->user()->id), 'Pengaturan notifikasi diperbarui');
    }

    /**
     * @return array<int, array{type: string, via_push: bool, via_email: bool, via_inapp: bool}>
     */
    private function rows(string $userId): array
    {
        $rows = [];

        foreach (Notification::TYPES as $type) {
            $setting = NotificationSetting::firstOrCreate(
                ['user_id' => $userId, 'type' => $type],
                ['via_push' => true, 'via_email' => true, 'via_inapp' => true]
            );

            $rows[] = [
                'type' => $setting->type,
                'via_push' => (bool) $setting->via_push,
                'via_email' => (bool) $setting->via_email,
                'via_inapp' => (bool) $setting->via_inapp,
            ];
        }

        return $rows;
    }
}
