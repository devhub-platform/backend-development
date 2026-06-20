<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this['id'],
            'title'       => $this['title'],
            'excerpt'     => $this['excerpt'],
            'cover_image' => $this['cover_image'],
            'image_url'   => $this['image_url'],
            'author'      => $this['author'],

            'views'           => $this['views'],
            'comments_count'  => $this['comments_count'],
            'reactions_count' => $this['reactions_count'],
            'tags'            => $this['tags'],

            'trending_score' => $this['trending_score'],
            'has_embedding'  => $this['has_embedding'],
            'created_at'     => $this['created_at'],
        ];
    }
}
