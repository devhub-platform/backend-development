<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class NewPostNotification extends Notification
{
    protected Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->post->user->name . ' published a new post',
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'post_slug' => $this->post->slug,
            'author_id' => $this->post->user_id,
            'author_name' => $this->post->user->name,
            'author_username' => $this->post->user->username,
            'author_avatar' => $this->post->user->avatar_url,
            'created_at' => $this->post->created_at->diffForHumans(),
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'author_name' => $this->post->user->name,
        ];
    }
}
