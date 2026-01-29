<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MentionInCommentNotification extends Notification
{
    protected Comment $comment;
    protected User $mentionedBy;

    public function __construct(Comment $comment, User $mentionedBy)
    {
        $this->comment = $comment;
        $this->mentionedBy = $mentionedBy;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You were mentioned in a comment')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->mentionedBy->username . ' mentioned you in a comment.')
            ->line('Comment: "' . Str::limit($this->comment->content, 100) . '"')
            ->action('View Comment', url('/posts/' . $this->comment->post_id))
            ->line('Thank you for being part of our community!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->mentionedBy->username . ' mentioned you in a comment',
            'comment_id' => $this->comment->id,
            'comment_content' => Str::limit($this->comment->content, 150),
            'post_id' => $this->comment->post_id,
            'post_title' => $this->comment->post->title ?? null,
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by_name' => $this->mentionedBy->name,
            'mentioned_by_username' => $this->mentionedBy->username,
            'mentioned_by_avatar' => $this->mentionedBy->avatar_url,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
