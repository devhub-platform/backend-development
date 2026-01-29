<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** @mixin Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'ID' => $this->id,
            'Title' => $this->title,
            'Content' => Str::limit($this->content, 200, '...'),
            'Created_at' => $this->created_at->diffForHumans(),
            'Updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'Image_url' => $this->cover_image ?? null,
            'Cover_image' => $this->image_url ?? null,
            'Status' => $this->status,
            'Read_time' => $this->read_time ? $this->read_time . ' min read' : null,
            'views' => $this->views ?? 0,
            'is_edit' => (bool) $this->is_edit,

            'user' => [
                'Name' => $this->user->name,
                'Username' => $this->user->username,
                'Avatar_Image' => $this->user->avatar_url ?? null,
            ],

            'reaction' => [
                'reaction with count' => $this->getReactionsWithCount(),
                'comments_count' => $this->comments()->count(),
            ],

            'Tags' => TagResource::collection($this->whenLoaded('tags')),
            'Comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
