<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    protected $comment;

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'New comment on your post: ' . $this->comment->post->title,
            'commenter_user' => $this->comment->user,
            'comment_body' => $this->comment->content,
        ];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
