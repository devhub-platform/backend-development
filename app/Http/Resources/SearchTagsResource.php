<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tag */
class SearchTagsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'posts_count' => $this->posts_count,
            'posts' => SearchPostResource::collection($this->whenLoaded('posts')),
        ];
    }
}
