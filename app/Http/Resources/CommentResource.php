<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'content' => $this->content,

            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name ?? 'Unknown User',
                'username' => $this->user->username ?? null,
                'avatar_url' => $this->user->avatar_url ?? null,
            ],

            // Comment status
            'is_pinned' => (bool) ($this->is_pinned ?? false),
            'is_reply' => $this->parent_id !== null,
            'is_edited' => $this->updated_at->gt($this->created_at->addSeconds(60)),

            'parent_id' => $this->parent_id,
            'depth' => $this->when($this->parent_id !== null, function() {
                return method_exists($this->resource, 'getDepth') ? $this->getDepth() : null;
            }),

            'replies_count' => $this->whenCounted('replies') ?? 0,

            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans(),


            'post' => new TrendingPostResource($this->whenLoaded('post')),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'full_user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
