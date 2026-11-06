<?php

namespace App\Notifications;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-05.3.4 — seseorang menyebut (@mention) pengguna di komentar.
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class ForumMention extends Notification
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
            ->subject('Melak — Anda disebut di forum')
            ->line($this->actor->name.' menyebut Anda pada "'.$this->post->title.'"')
            ->action('Buka Forum', config('app.url').'/api/forum/posts/'.$this->post->id)
            ->line(Unsubscribe::line($notifiable, 'mention'));
    }
}
