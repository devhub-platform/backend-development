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
            'Tile' => $this->title,
            'Content' => Str::limit($this->content, 200, '...'),
            'Author' => $this->user->name,
            'Created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'Updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'Image url' => $this->image_url,
            'Cover image' => $this->image_url ? Storage::url($this->image_url) : null,
            'Status' => $this->status,
            'Read time' => $this->read_time . ' min read',
            'Tags' => TagResource::collection($this->whenLoaded('tags')),
            'Comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
