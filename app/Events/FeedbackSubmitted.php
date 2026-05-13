<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Feedback $feedback
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'feedback.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->feedback->id,
            'title' => $this->feedback->title ?? 'New Feedback',
            'message' => $this->feedback->message ?? $this->feedback->content,
            'user_id' => $this->feedback->user_id,
            'user_name' => $this->feedback->user?->name ?? 'Anonymous',
            'type' => $this->feedback->type ?? 'feedback',
            'rating' => $this->feedback->rating ?? null,
            'created_at' => $this->feedback->created_at,
        ];
    }
}
