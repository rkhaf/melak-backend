<?php

namespace App\Notifications;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * FR-05.3.4 — balasan pada post yang diikuti / post sendiri.
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class ForumReply extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ForumPost $post,
        public readonly ForumComment $comment,
        public readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Melak — Balasan baru di forum')
            ->line($this->actor->name.' membalas pada "'.$this->post->title.'"')
            ->line(Str::limit(strip_tags($this->comment->content_html), 160))
            ->action('Buka Forum', config('app.url').'/api/forum/posts/'.$this->post->id)
            ->line(Unsubscribe::line($notifiable, 'forum_reply'));
    }
}
