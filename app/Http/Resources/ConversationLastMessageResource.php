<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationLastMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $senderId = $this->sender?->id;
        $isCurrentUserSender = $request->user() && $senderId === $request->user()->id;
        
        return [
            'id' => $this->id,
            'body' => $this->body,
            'type' => $this->type,
            'is_seen' => (bool)$this->is_seen,
            'is_sender' => $isCurrentUserSender,
            'sender_id' => $senderId,
            'created_at' => $this->created_at?->diffForHumans(),
            'sender' => new MessageParticipantResource($this->sender),
        ];
    }
}

