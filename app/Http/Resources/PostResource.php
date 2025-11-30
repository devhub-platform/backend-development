<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'ID' => $this->id,
            'Tile' => $this->title,
            'Content' => $this->content,
            'Author_id' => $this->user_id,
            'Created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'Updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'Image_url' => $this->image_url,
            'Status' => $this->status,
        ];
    }
}
