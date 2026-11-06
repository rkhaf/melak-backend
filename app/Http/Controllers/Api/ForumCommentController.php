<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumComment;
use App\Models\ForumFollow;
use App\Models\ForumPost;
use App\Models\ForumVote;
use App\Models\User;
use App\Notifications\ForumMention;
use App\Notifications\ForumReply;
use App\Support\Notifier;
use App\Support\Reputation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Komentar forum (API.md §6.11 — §6.15, FR-05.3).
 */
class ForumCommentController extends Controller
{
    use ApiResponse, ForumPresenter;

    /**
     * POST /api/forum/posts/{id}/comments (API.md §6.11).
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $post = ForumPost::find($postId);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'parent_id' => 'sometimes|nullable|integer',
            'content_html' => 'required|string|max:10000',
            'image_urls' => 'sometimes|array|max:3',
            'image_urls.*' => 'url|max:500',
        ], [
            'content_html.required' => 'Isi komentar wajib diisi.',
            'image_urls.max' => 'Maksimal 3 foto per komentar.',
        ]);

        $parent = null;

        if (! empty($data['parent_id'])) {
            $parent = ForumComment::find($data['parent_id']);

            if (! $parent || $parent->post_id !== $post->id) {
                return $this->fail('Komentar induk tidak valid.', 422);
            }

            if ($parent->parent_id !== null) {
                return $this->fail('Balasan maksimal 2 level.', 422);
            }
        }

        $comment = ForumComment::create([
            'post_id' => $post->id,
            'parent_id' => $parent?->id,
            'user_id' => $request->user()->id,
            'content_html' => $data['content_html'],
            'images' => $data['image_urls'] ?? [],
            'votes_count' => 0,
            'is_best_answer' => false,
        ]);

        $post->increment('comments_count');

        $this->notifyParticipants($request->user(), $post, $comment, $parent);

        $comment->load('user:id,name,reputation_level');

        return $this->created($this->commentPayload($comment), 'Komentar berhasil ditambahkan');
    }

    /**
     * PUT /api/forum/comments/{id} (API.md §6.12) — author only.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $comment = ForumComment::find($id);

        if (! $comment) {
            return $this->fail('Komentar tidak ditemukan.', 404);
        }

        if ($comment->user_id !== $request->user()->id) {
            return $this->fail('Anda hanya dapat mengubah komentar sendiri.', 403);
        }

        $data = $request->validate([
            'content_html' => 'required|string|max:10000',
        ], ['content_html.required' => 'Isi komentar wajib diisi.']);

        $comment->update(['content_html' => $data['content_html']]);
        $comment->load('user:id,name,reputation_level');

        return $this->ok($this->commentPayload($comment), 'Komentar berhasil diperbarui');
    }

    /**
     * DELETE /api/forum/comments/{id} (API.md §6.13) — author / moderator.
     */
    public function destroy(Request $request, int $id): Response
    {
        $comment = ForumComment::find($id);

        if (! $comment) {
            return $this->fail('Komentar tidak ditemukan.', 404);
        }

        if ($comment->user_id !== $request->user()->id && ! $this->canModerate($request->user())) {
            return $this->fail('Anda tidak memiliki akses menghapus komentar ini.', 403);
        }

        ForumPost::whereKey($comment->post_id)->decrement('comments_count');
        $comment->delete();

        return $this->noContent();
    }

    /**
     * POST /api/forum/comments/{id}/vote (API.md §6.14).
     */
    public function vote(Request $request, int $id): JsonResponse
    {
        $comment = ForumComment::find($id);

        if (! $comment) {
            return $this->fail('Komentar tidak ditemukan.', 404);
        }

        $data = $request->validate(['value' => 'required|integer|in:-1,0,1']);

        return $this->ok($this->applyVote(
            $request->user(),
            $comment,
            ForumComment::class,
            (int) $data['value'],
            'forum_comment'
        ));
    }

    /**
     * POST /api/forum/comments/{id}/best (API.md §6.15, FR-05.3.3).
     */
    public function markBest(Request $request, int $id): JsonResponse
    {
        $comment = ForumComment::with('post')->find($id);

        if (! $comment) {
            return $this->fail('Komentar tidak ditemukan.', 404);
        }

        $post = $comment->post;

        if ($post->user_id !== $request->user()->id) {
            return $this->fail('Hanya penulis post yang dapat menandai jawaban terbaik.', 403);
        }

        $alreadyBest = (bool) $comment->is_best_answer;

        ForumComment::where('post_id', $post->id)->update(['is_best_answer' => false]);

        if (! $alreadyBest) {
            $comment->update(['is_best_answer' => true]);
            $post->update(['has_best_answer' => true]);

            Reputation::award(User::find($comment->user_id), 10, 'jawaban_terbaik', 'forum_comment', $comment->id);
        } else {
            $post->update(['has_best_answer' => ForumComment::where('post_id', $post->id)->where('is_best_answer', true)->exists()]);
        }

        return $this->ok([
            'is_best_answer' => ! $alreadyBest,
            'votes_count' => $comment->votes_count,
        ]);
    }

    private function applyVote(User $user, ForumComment $comment, string $morph, int $value, string $refType): array
    {
        $vote = ForumVote::where('user_id', $user->id)
            ->where('votable_type', $morph)
            ->where('votable_id', $comment->id)
            ->first();

        $previous = $vote ? (int) $vote->value : 0;

        if ($value === 0) {
            $vote?->delete();
        } else {
            ForumVote::updateOrCreate(
                ['user_id' => $user->id, 'votable_type' => $morph, 'votable_id' => $comment->id],
                ['value' => $value]
            );
        }

        $comment->votes_count = (int) ForumVote::where('votable_type', $morph)
            ->where('votable_id', $comment->id)
            ->sum('value');
        $comment->save();

        if ($value === 1 && $previous !== 1 && $comment->user_id !== $user->id) {
            $author = User::find($comment->user_id);

            if ($author) {
                Reputation::award($author, 1, 'upvote_diterima', $refType, $comment->id);
            }
        }

        return ['votes_count' => $comment->votes_count, 'my_vote' => $value];
    }

    private function notifyParticipants(User $author, ForumPost $post, ForumComment $comment, ?ForumComment $parent): void
    {
        $body = sprintf('%s membalas pada "%s"', $author->name, $post->title);
        $payload = ['post_id' => $post->id, 'comment_id' => $comment->id];

        $mentions = $this->mentionsIn($comment->content_html);

        if ($mentions !== []) {
            $users = User::whereIn('username', $mentions)->where('id', '!=', $author->id)->get();

            foreach ($users as $mentioned) {
                Notifier::send(
                    $mentioned,
                    'mention',
                    'Anda disebut di forum',
                    sprintf('%s menyebut Anda pada "%s"', $author->name, $post->title),
                    $payload,
                    fn () => $mentioned->notify(new ForumMention($post, $comment, $author)),
                );
            }
        }

        $recipientIds = collect()
            ->push($post->user_id)
            ->push($parent?->user_id)
            ->merge(ForumFollow::where('post_id', $post->id)->pluck('user_id'))
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $author->id);

        $recipients = User::whereIn('id', $recipientIds)->get(['id', 'name', 'email']);

        foreach ($recipients as $recipient) {
            Notifier::send(
                $recipient,
                'forum_reply',
                'Balasan baru di forum',
                $body,
                $payload,
                fn () => $recipient->notify(new ForumReply($post, $comment, $author)),
            );
        }
    }
}
