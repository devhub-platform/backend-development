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
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->avatar_url,
            'content' => $this->content,
            'is_pinned' => $this->is_pinned ?? false,
            'is_reply' => $this->parent_id !== null,
            'parent_id' => $this->parent_id,
            'replies_count' => $this->whenCounted('replies'),
            'reactions_count' => $this->when(method_exists($this->resource, 'getReactionsWithCount'), fn() => $this->getReactionsWithCount()),
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'is_edited' => $this->updated_at->gt($this->created_at->addSeconds(60)),
            'post' => new PostResource($this->whenLoaded('post')),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
