<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'created_at' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'image_url' => $this->image_url ?? [],
            'cover_image' => $this->cover_image ?? null,
            'status' => $this->status,
            'read_time' => $this->read_time ? $this->read_time . ' min read' : null,
            'views' => $this->views ?? 0,
            'is_edited' => (bool) $this->is_edit,

            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_image' => $this->user->avatar_url ?? null,
            ],

            'reaction' => [
                'reaction_with_count' => $this->getReactionsWithCount(),
                'comments_count' => $this->comments()->count(),
            ],

            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
