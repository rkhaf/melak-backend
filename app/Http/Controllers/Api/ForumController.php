<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumBookmark;
use App\Models\ForumCategory;
use App\Models\ForumFollow;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\ForumTag;
use App\Models\ForumVote;
use App\Models\User;
use App\Support\Reputation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forum komunitas — post (API.md §6.1 — §6.10, FR-05).
 */
class ForumController extends Controller
{
    use ApiResponse, ForumPresenter;

    /**
     * GET /api/forum/categories (API.md §6.1).
     */
    public function categories()
    {
        $categories = ForumCategory::orderBy('sort_order')->orderBy('name')->get();

        return $this->ok($categories->map(fn (ForumCategory $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'description' => $category->description,
            'post_count' => $category->post_count,
        ])->values()->all());
    }

    /**
     * GET /api/forum/posts (API.md §6.2, FR-05.1.3).
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'category' => 'sometimes|string|max:80',
            'sort' => 'sometimes|in:terbaru,trending,belum_terjawab,mengikuti',
            'type' => 'sometimes|in:question,discussion',
            'tag' => 'sometimes|string|max:80',
            'q' => 'sometimes|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $isModerator = $this->canModerate($user);

        $query = ForumPost::query()
            ->with(['user:id,name,avatar_url,reputation_level', 'category:id,name,slug,icon', 'tags:id,slug', 'scan.result.disease:id,name'])
            ->when(! $isModerator, function ($q) use ($user) {
                $q->where(function ($q) use ($user) {
                    $q->where('status', 'published')
                        ->when($user !== null, fn ($scope) => $scope->orWhere('user_id', $user->id));
                });
            })
            ->when($user === null, fn ($q) => $q->where('status', 'published'))
            ->when(! empty($data['category']), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $data['category'])))
            ->when(! empty($data['type']), fn ($q) => $q->where('type', $data['type']))
            ->when(! empty($data['tag']), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $data['tag'])))
            ->when(! empty($data['q']), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('title', 'like', "%{$data['q']}%")
                ->orWhere('content_html', 'like', "%{$data['q']}%")));

        $sort = $data['sort'] ?? 'terbaru';

        match ($sort) {
            'trending' => $query->orderByDesc(DB::raw('votes_count + comments_count + views_count')),
            'belum_terjawab' => $query->where('type', 'question')->where('has_best_answer', false)->orderByDesc('created_at'),
            'mengikuti' => $query->whereHas('follows', fn ($f) => $f->where('user_id', $user?->id))->orderByDesc('created_at'),
            default => $query->orderByDesc('is_pinned')->orderByDesc('created_at'),
        };

        $page = $query->paginate((int) ($data['per_page'] ?? 15))->withQueryString();

        return $this->paginated($page->through(fn (ForumPost $post) => $this->postPayload($post)));
    }

    /**
     * POST /api/forum/posts (API.md §6.3, FR-05.2).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:question,discussion',
            'title' => 'required|string|min:5|max:180',
            'content_html' => 'required|string|max:20000',
            'category_id' => 'required|integer|exists:forum_categories,id',
            'tags' => 'sometimes|array|max:5',
            'tags.*' => 'string|max:30',
            'image_urls' => 'sometimes|array|max:5',
            'image_urls.*' => 'url|max:500',
            'linked_scan_id' => ['sometimes', 'uuid', Rule::exists('fish_scans', 'id')->where('user_id', $request->user()->id)],
        ], [
            'type.required' => 'Jenis post wajib dipilih.',
            'type.in' => 'Jenis post tidak valid.',
            'title.required' => 'Judul wajib diisi.',
            'title.min' => 'Judul minimal 5 karakter.',
            'title.max' => 'Judul maksimal 180 karakter.',
            'content_html.required' => 'Isi post wajib diisi.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'tags.max' => 'Maksimal 5 tag.',
            'image_urls.max' => 'Maksimal 5 foto.',
            'linked_scan_id.exists' => 'Scan tidak ditemukan.',
        ]);

        $user = $request->user();
        $status = $this->moderationStatus($user, $data['content_html']);

        $post = ForumPost::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'scan_id' => $data['linked_scan_id'] ?? null,
            'title' => $data['title'],
            'content_html' => $data['content_html'],
            'image_urls' => $data['image_urls'] ?? [],
            'type' => $data['type'],
            'status' => $status,
            'votes_count' => 0,
            'comments_count' => 0,
            'views_count' => 0,
        ]);

        ForumCategory::whereKey($data['category_id'])->increment('post_count');

        foreach ($data['tags'] ?? [] as $tagName) {
            $tag = $this->resolveTag($tagName);
            $post->tags()->attach($tag->id);
            $tag->increment('usage_count');
        }

        if ($status === 'published') {
            Reputation::award($user, 2, 'post_baru', 'forum_post', $post->id);
        }

        $post->load(['user:id,name,avatar_url,reputation_level', 'category:id,name,slug,icon', 'tags:id,slug']);

        return $this->created($this->postPayload($post, true), 'Post berhasil dibuat');
    }

    /**
     * GET /api/forum/posts/{id} (API.md §6.4).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::with([
            'user:id,name,avatar_url,reputation_level',
            'category:id,name,slug,icon',
            'tags:id,slug',
            'scan.result.disease:id,name',
        ])->find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $user = $request->user();

        if ($post->status !== 'published') {
            $allowed = $user && ($post->user_id === $user->id || $this->canModerate($user));

            if (! $allowed) {
                return $this->fail('Post tidak ditemukan.', 404);
            }
        }

        $this->countView($post, $user);

        $payload = $this->postPayload($post, true);

        $payload['comments'] = $post->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name,reputation_level', 'replies.user:id,name,reputation_level'])
            ->orderBy('created_at')
            ->paginate(15)
            ->through(fn ($comment) => $this->commentPayload($comment))
            ->toArray();

        $payload['viewer'] = [
            'is_bookmarked' => $user ? ForumBookmark::where('user_id', $user->id)->where('post_id', $post->id)->exists() : false,
            'is_following' => $user ? ForumFollow::where('user_id', $user->id)->where('post_id', $post->id)->exists() : false,
            'my_vote' => $user ? (int) ForumVote::where('user_id', $user->id)
                ->where('votable_type', ForumPost::class)
                ->where('votable_id', $post->id)
                ->value('value') : 0,
        ];

        return $this->ok($payload);
    }

    /**
     * PUT /api/forum/posts/{id} (API.md §6.5) — author only.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $user = $request->user();

        if ($post->user_id !== $user->id) {
            return $this->fail('Anda hanya dapat mengubah post sendiri.', 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|min:5|max:180',
            'content_html' => 'sometimes|string|max:20000',
            'category_id' => 'sometimes|integer|exists:forum_categories,id',
            'tags' => 'sometimes|array|max:5',
            'tags.*' => 'string|max:30',
        ]);

        $post->fill(array_intersect_key($data, array_flip(['title', 'content_html', 'category_id'])));
        $post->save();

        if (array_key_exists('tags', $data)) {
            foreach ($post->tags as $oldTag) {
                $post->tags()->detach($oldTag->id);
                $oldTag->decrement('usage_count');
            }

            foreach ($data['tags'] as $tagName) {
                $tag = $this->resolveTag($tagName);
                $post->tags()->attach($tag->id);
                $tag->increment('usage_count');
            }
        }

        $post->load(['user:id,name,avatar_url,reputation_level', 'category:id,name,slug,icon', 'tags:id,slug']);

        return $this->ok($this->postPayload($post, true), 'Post berhasil diperbarui');
    }

    /**
     * DELETE /api/forum/posts/{id} (API.md §6.6) — author / moderator.
     */
    public function destroy(Request $request, int $id): Response
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        if ($post->user_id !== $request->user()->id && ! $this->canModerate($request->user())) {
            return $this->fail('Anda tidak memiliki akses menghapus post ini.', 403);
        }

        ForumCategory::whereKey($post->category_id)->decrement('post_count');
        $post->delete();

        return $this->noContent();
    }

    /**
     * POST /api/forum/posts/{id}/vote (API.md §6.7, FR-05.4.1).
     */
    public function vote(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $data = $request->validate(['value' => 'required|integer|in:-1,0,1']);

        return $this->ok($this->applyVote(
            $request->user(),
            $post,
            ForumPost::class,
            (int) $data['value'],
            'forum_post'
        ));
    }

    /**
     * POST /api/forum/posts/{id}/bookmark (API.md §6.8).
     */
    public function bookmark(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $existing = ForumBookmark::where('user_id', $request->user()->id)->where('post_id', $post->id)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            ForumBookmark::create(['user_id' => $request->user()->id, 'post_id' => $post->id]);
            $bookmarked = true;
        }

        return $this->ok(['is_bookmarked' => $bookmarked]);
    }

    /**
     * POST /api/forum/posts/{id}/follow (API.md §6.9).
     */
    public function follow(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $existing = ForumFollow::where('user_id', $request->user()->id)->where('post_id', $post->id)->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            ForumFollow::create(['user_id' => $request->user()->id, 'post_id' => $post->id]);
            $following = true;
        }

        return $this->ok(['is_following' => $following]);
    }

    /**
     * POST /api/forum/posts/{id}/report (API.md §6.10, FR-05.5.1).
     */
    public function report(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::find($id);

        if (! $post) {
            return $this->fail('Post tidak ditemukan.', 404);
        }

        $data = $request->validate([
            'reason' => 'required|in:spam,konten_kasar,hoax,lainnya',
            'notes' => 'nullable|string|max:500',
        ], [
            'reason.required' => 'Alasan laporan wajib diisi.',
            'reason.in' => 'Alasan laporan tidak valid.',
        ]);

        ForumReport::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        return $this->created(['reported' => true], 'Laporan masuk antrean moderasi');
    }

    /**
     * @return array{votes_count: int, my_vote: int}
     */
    protected function applyVote(User $user, $model, string $morph, int $value, string $refType): array
    {
        $vote = ForumVote::where('user_id', $user->id)
            ->where('votable_type', $morph)
            ->where('votable_id', $model->id)
            ->first();

        $previous = $vote ? (int) $vote->value : 0;

        if ($value === 0) {
            $vote?->delete();
        } else {
            ForumVote::updateOrCreate(
                ['user_id' => $user->id, 'votable_type' => $morph, 'votable_id' => $model->id],
                ['value' => $value]
            );
        }

        $model->votes_count = (int) ForumVote::where('votable_type', $morph)
            ->where('votable_id', $model->id)
            ->sum('value');
        $model->save();

        if ($value === 1 && $previous !== 1 && $model->user_id !== $user->id) {
            $author = User::find($model->user_id);

            if ($author) {
                Reputation::award($author, $refType === 'forum_post' ? 2 : 1, 'upvote_diterima', $refType, $model->id);
            }
        }

        return ['votes_count' => $model->votes_count, 'my_vote' => $value];
    }

    /**
     * Semua post tayang langsung — tidak ada pramoderasi berdasarkan usia
     * akun (FR-05.5). Yang masih masuk antrean moderasi hanya konten yang
     * mengandung kata terblokir (FORUM_BLOCKED_WORDS, default kosong).
     */
    protected function moderationStatus(User $user, string $content): string
    {
        $blocked = config('ngingu.forum.blocked_words', []);

        foreach ($blocked as $word) {
            if ($word !== '' && stripos($content, $word) !== false) {
                return 'pending';
            }
        }

        return 'published';
    }

    protected function resolveTag(string $name): ForumTag
    {
        $slug = Str::slug($name);

        return ForumTag::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'usage_count' => 0]
        );
    }

    private function countView(ForumPost $post, ?User $user): void
    {
        $key = sprintf('forum:view:%d:%s', $post->id, $user?->id ?: request()->ip());

        if (Cache::add($key, 1, 3600)) {
            $post->increment('views_count');
        }
    }
}
