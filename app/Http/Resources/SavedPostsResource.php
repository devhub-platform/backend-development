<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SavedPostsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'author' => $this->user->name,
            'id' => $this->id,
            'title' => $this->title,
            'content' => Str::take($this->content, 100) . '...',
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
