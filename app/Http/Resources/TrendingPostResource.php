<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrendingPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => data_get($this, 'id'),
            'title'           => data_get($this, 'title'),
            'content'         => data_get($this, 'content'),

            'views'           => data_get($this, 'views', 0),

            'trending_score'  => data_get($this, 'trending_score', 0),

            'tags'            => data_get($this, 'tags', []),

            'has_embedding'   => data_get($this, 'has_embedding', false),
        ];
    }
}
