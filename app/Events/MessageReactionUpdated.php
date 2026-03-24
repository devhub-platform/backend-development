<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $conversationId,
        public int $userId,
        public string $reaction,
        public string $action,
        public array $reactions
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('mc-chat-conversation.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'message.reaction.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'reaction' => $this->reaction,
            'action' => $this->action,
            'reactions' => $this->reactions,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}

