<?php

namespace App\Http\Resources;

use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserStatus */
class UserStatusesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emoji' => $this->emoji,
            'status_text' => $this->status_text,
            'is_busy' => $this->is_busy,
            'clear_after' => $this->clear_after,
//            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
