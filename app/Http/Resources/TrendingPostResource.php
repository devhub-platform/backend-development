<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => data_get($this, 'id'),
            'title'          => data_get($this, 'title'),
            'content'        => data_get($this, 'content'),
            'cover_image'    => data_get($this, 'cover_image'),
            'image_url'      => data_get($this, 'image_url', []),

            'author'         => data_get($this, 'author'),

            'views'          => data_get($this, 'views', 0),
            'trending_score' => data_get($this, 'trending_score', 0),
            'has_embedding'  => data_get($this, 'has_embedding', false),

            'tags'           => data_get($this, 'tags', []),

            'comments_count' => data_get($this, 'comments_count', 0),
            'comments'       => data_get($this, 'comments', []),

            'reactions_count' => data_get($this, 'reactions_count', 0),
            'reactions'       => data_get($this, 'reactions', []),

            'generated_images' => data_get($this, 'generated_images', []),
        ];
    }
}
