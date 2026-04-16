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
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar,
            ],
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->status,
            'rating' => $this->rating,
            'attachments' => $this->attachments,
//            'admin_response' => $this->admin_response,
//            'responded_by' => $this->respondedBy ? [
//                'id' => $this->respondedBy->id,
//                'name' => $this->respondedBy->name,
//                'email' => $this->respondedBy->email,
//            ] : null,
            'responded_at' => $this->responded_at,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}

