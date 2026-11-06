<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Notifications\PostModerated;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Http\Request;

/**
 * FR-05.5.2 — FR-05.5.4 / FR-09.1.2 — moderasi post forum.
 */
class ForumModerationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $statuses = ['pending', 'published', 'rejected', 'locked'];

        if (! in_array($status, [...$statuses, 'all'], true)) {
            $status = 'pending';
        }

        $posts = ForumPost::with(['user:id,name,username', 'category:id,name,slug'])
            ->withCount([
                'comments',
                'reports' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->q}%")
                    ->orWhere('content_html', 'like', "%{$request->q}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = collect($statuses)
            ->mapWithKeys(fn ($s) => [$s => ForumPost::where('status', $s)->count()])
            ->all();
        $counts['all'] = ForumPost::count();
        $counts['reported'] = ForumPost::whereHas('reports', fn ($q) => $q->where('status', 'pending'))->count();

        return view('admin.forum.index', compact('posts', 'status', 'counts'));
    }

    public function approve(Request $request, ForumPost $post)
    {
        if ($post->status === 'published') {
            return back()->with('info', 'Post sudah tayang.');
        }

        $post->update(['status' => 'published']);
        Audit::log('forum.approved', 'Post "'.$post->title.'" disetujui dan ditayangkan.', $post);
        $this->notifyAuthor($post, 'approved', $request->input('note'));

        return redirect()
            ->route('admin.forum.index', array_filter(['status' => 'pending']))
            ->with('success', 'Post "'.$post->title.'" disetujui.');
    }

    public function reject(Request $request, ForumPost $post)
    {
        if ($post->status === 'rejected') {
            return back()->with('info', 'Post sudah ditolak.');
        }

        $post->update(['status' => 'rejected']);
        Audit::log('forum.rejected', 'Post "'.$post->title.'" ditolak.', $post);
        $this->notifyAuthor($post, 'rejected', $request->input('note'));

        return redirect()
            ->route('admin.forum.index', array_filter(['status' => 'pending']))
            ->with('success', 'Post "'.$post->title.'" ditolak.');
    }

    public function destroy(ForumPost $post)
    {
        $title = $post->title;
        $post->delete();
        Audit::log('forum.deleted', 'Post "'.$title.'" dihapus permanen.');

        return redirect()->route('admin.forum.index')->with('success', 'Post "'.$title.'" dihapus.');
    }

    public function pin(ForumPost $post)
    {
        $post->update(['is_pinned' => ! $post->is_pinned]);
        Audit::log('forum.pinned', 'Post "'.$post->title.'" '.($post->is_pinned ? 'dipasang pin' : 'lepas pin').'.', $post);

        return back()->with('success', 'Post "'.$post->title.'" '.($post->is_pinned ? 'dipasang pin' : 'lepas pin').'.');
    }

    public function lock(ForumPost $post)
    {
        if ($post->status === 'locked') {
            $post->update(['status' => 'published']);
            $action = 'dibuka kunci';
        } else {
            $post->update(['status' => 'locked']);
            $action = 'dikunci';
        }

        Audit::log('forum.locked', 'Thread "'.$post->title.'" '.$action.'.', $post);

        return back()->with('success', 'Thread "'.$post->title.'" '.$action.'.');
    }

    private function notifyAuthor(ForumPost $post, string $decision, ?string $note): void
    {
        $author = $post->user;

        if ($author === null) {
            return;
        }

        $title = $decision === 'approved' ? 'Post Anda disetujui' : 'Post Anda ditolak';
        $body = $decision === 'approved'
            ? 'Post "'.$post->title.'" telah disetujui dan tayang di forum.'
            : 'Post "'.$post->title.'" ditolak oleh moderator.';

        Notifier::send(
            $author,
            'forum_reply',
            $title,
            $body,
            ['post_id' => $post->id, 'decision' => $decision],
            fn () => $author->notify(new PostModerated($post, $decision, $note)),
        );
    }
}
