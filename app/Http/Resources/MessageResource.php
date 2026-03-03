<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'type' => $this->type,
            'data' => $this->data,
            'is_seen' => (bool)$this->is_seen,
            'is_sender' => (bool)$this->is_sender,
            'created_at' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'sender' => new MessageParticipantResource($this->sender),
        ];
    }
}
