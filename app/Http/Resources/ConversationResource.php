<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conversation = $this->conversation ?? $this->resource;

        if ($this->resource instanceof \Musonza\Chat\Models\Conversation) {
            $conversation = $this->resource;
            return $this->formatConversation($conversation);
        }

        return $this->formatConversation($conversation);
    }

    private function formatConversation($conversation): array
    {
        $participants = $conversation->participants ?? collect();

        return [
            'id' => $conversation->id,
            'is_direct' => (bool)$conversation->direct_message,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'last_message' => $conversation->last_message
                ? new ConversationLastMessageResource($conversation->last_message)
                : null,
            'participants' => $participants->map(fn($p) => [
                'id' => $p->messageable?->id,
                'name' => $p->messageable?->name,
                'username' => $p->messageable?->username,
                'avatar_url' => $p->messageable?->avatar_url,
            ])->values(),
        ];
    }
}

