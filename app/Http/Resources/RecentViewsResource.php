<?php

namespace App\Http\Resources;

use App\Models\PostView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin PostView */
class RecentViewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ID' => $this->id,
            'title' => $this->post->title,
            'content' => Str::take($this->post->content, 100) . '...',
            'author' => $this->post->user->name,
            'Viewed_at' => $this->created_at->diffForHumans(),
        ];
    }
}
