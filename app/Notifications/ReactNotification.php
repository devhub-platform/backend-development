<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class ReactNotification extends Notification
{
    protected $post;
    protected $reactionType;
    protected $sender;

    public function __construct($post, $reactionType, ?User $sender = null)
    {
        $this->post = $post;
        $this->reactionType = $reactionType;
        $this->sender = $sender;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'New reaction on your post: ' . optional($this->post)->title,
            'from' => $this->senderPayload(),
            'reaction_type' => $this->reactionType ?? null,
        ];
    }

    private function senderPayload(): ?array
    {
        if (!$this->sender) {
            return null;
        }

        return [
            'id' => $this->sender->id,
            'name' => $this->sender->name,
            'username' => $this->sender->username,
            'avatar_url' => $this->sender->avatar_url,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
