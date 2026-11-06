<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi in-app (API.md §8.1 — §8.3, FR-06.1 — FR-06.3).
 */
class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/notifications (API.md §8.1).
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'sometimes|in:scan_result,forum_reply,mention,outbreak,tips,security,weekly',
            'unread_only' => 'sometimes|in:true,false,1,0',
            'page' => 'sometimes|integer|min:1',
        ]);

        $query = Notification::where('user_id', $request->user()->id)
            ->when(! empty($data['type']), fn ($q) => $q->where('type', $data['type']))
            ->when(filter_var($data['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN), fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $page = $query->paginate(20)->withQueryString();

        $unreadCount = Notification::where('user_id', $request->user()->id)->unread()->count();

        return $this->paginated($page, 'Berhasil', ['unread_count' => $unreadCount]);
    }

    /**
     * PATCH /api/notifications/{id}/read (API.md §8.2).
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)->find($id);

        if (! $notification) {
            return $this->fail('Notifikasi tidak ditemukan.', 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $this->ok($this->payload($notification), 'Notifikasi ditandai dibaca');
    }

    /**
     * PATCH /api/notifications/read-all (API.md §8.3).
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return $this->ok(['updated' => $updated], 'Semua notifikasi ditandai dibaca');
    }

    private function payload(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data ?? [],
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
