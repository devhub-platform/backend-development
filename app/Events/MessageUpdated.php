<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Musonza\Chat\Models\Message;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int     $conversationId
    )
    {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('mc-chat-conversation.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'type' => $this->message->type,
            'data' => $this->message->data,
            'conversation_id' => $this->conversationId,
            'updated_at' => $this->message->updated_at?->toIso8601String(),
        ];
    }
}

