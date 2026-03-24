<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Post */
class SearchPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'content' => Str::limit($this->content, 100),
            'posted_by' => new SearchUsersResource($this->whenLoaded('user')),
        ];
    }
}
