<?php

namespace App\Http\Controllers\Api;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Support\Str;

/**
 * Format payload forum sesuai API.md §6.2 / §6.4.
 */
trait ForumPresenter
{
    protected function canModerate(?User $user): bool
    {
        return $user !== null && in_array($user->role, ['admin', 'superadmin', 'moderator'], true);
    }

    protected function postPayload(ForumPost $post, bool $withContent = false): array
    {
        $payload = [
            'id' => $post->id,
            'type' => $post->type,
            'title' => $post->title,
            'excerpt' => Str::limit(trim(preg_replace('/<[^>]+>/', ' ', $post->content_html)), 140),
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
                'icon' => $post->category->icon,
            ] : null,
            'author' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'avatar_url' => $post->user->avatar_url,
                'reputation_level' => $post->user->reputation_level,
            ],
            'linked_scan' => $this->linkedScanPayload($post),
            'tags' => $post->tags->pluck('slug')->values()->all(),
            'votes_count' => $post->votes_count,
            'comments_count' => $post->comments_count,
            'views_count' => $post->views_count,
            'has_best_answer' => $post->has_best_answer,
            'is_pinned' => $post->is_pinned,
            'status' => $post->status,
            'created_at' => $post->created_at?->toIso8601String(),
        ];

        if ($withContent) {
            $payload['content_html'] = $post->content_html;
            $payload['image_urls'] = $post->image_urls ?? [];
            $payload['linked_scan_id'] = $post->scan_id;
        }

        return $payload;
    }

    protected function linkedScanPayload(ForumPost $post): ?array
    {
        if (! $post->scan_id) {
            return null;
        }

        $scan = $post->relationLoaded('scan') ? $post->scan : $post->scan()->first();

        if (! $scan) {
            return null;
        }

        $result = $scan->relationLoaded('result') ? $scan->result : $scan->result()->first();
        $image = $scan->images()->orderBy('order_index')->first();

        return [
            'scan_id' => $scan->id,
            'disease' => $result ? [
                'name' => $result->disease?->name,
                'confidence' => $result ? (float) $result->confidence_score : null,
            ] : null,
            'thumbnail_url' => $image
                ? app(ImageStorage::class)->url(app(ImageStorage::class)->thumbKey($image->s3_key))
                : null,
        ];
    }

    protected function commentPayload(ForumComment $comment, bool $withReplies = true): array
    {
        $payload = [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'author' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'reputation_level' => $comment->user->reputation_level,
            ],
            'content_html' => $comment->content_html,
            'images' => $comment->images ?? [],
            'votes_count' => $comment->votes_count,
            'is_best_answer' => $comment->is_best_answer,
            'mention_users' => $this->mentionsIn($comment->content_html),
            'created_at' => $comment->created_at?->toIso8601String(),
        ];

        if ($withReplies) {
            $payload['replies'] = $comment->replies()
                ->with('user:id,name,reputation_level')
                ->orderBy('created_at')
                ->get()
                ->map(fn (ForumComment $reply) => $this->commentPayload($reply, false))
                ->values()
                ->all();
        }

        return $payload;
    }

    protected function mentionsIn(string $html): array
    {
        preg_match_all('/@([a-z0-9._]{3,30})/i', strip_tags($html), $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1] ?? [])));
    }
}
