<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->status ?? 'new',
            'rating' => $this->rating,
            'attachment' => $this->attachments,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}

