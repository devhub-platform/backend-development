<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'content'     => $this->content,

            'views'       => $this->views,
            'created_at'  => $this->created_at,

            'trending_score' => $this->trending_score ?? null,

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'avatar' => $this->user?->avatar_url,
            ],

            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
        ];
    }
}
