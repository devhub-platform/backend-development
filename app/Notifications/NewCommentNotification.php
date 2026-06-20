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
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->post_id,
            'post_title' => $this->comment->post->title,
            'post_slug' => $this->comment->post->slug,
            'commenter_user' => [
                'id' => $this->comment->user?->id,
                'name' => $this->comment->user?->name,
                'username' => $this->comment->user?->username,
                'avatar_url' => $this->comment->user?->avatar_url,
                'role' => $this->comment->user?->role,
            ],
            'comment_body' => $this->comment->content,
        ];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
