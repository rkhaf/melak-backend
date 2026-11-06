<?php

namespace App\Notifications;

use App\Models\ForumPost;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-05.5.3 — pemberitahuan hasil moderasi post ke penulis.
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class PostModerated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ForumPost $post,
        public readonly string $decision,
        public readonly ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject('Melak — Post forum '.($approved ? 'disetujui' : 'ditolak'))
            ->line('Post "'.$this->post->title.'" '.($approved ? 'telah disetujui dan tayang di forum.' : 'ditolak oleh moderator.'))
            ->action('Buka Forum', config('app.url').'/api/forum/posts/'.$this->post->id);

        if ($this->note !== null && $this->note !== '') {
            $mail->line('Catatan moderator: '.$this->note);
        }

        $mail->line(Unsubscribe::line($notifiable, 'forum_reply'));

        return $mail;
    }
}
