<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ReadingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'post_count' => $this->posts_count,

            'auth_user' => [
                'name' => auth()->user()->name,
                'avatar_url' => auth()->user()->avatar_url,
            ],

            'posts' => $this->posts->map(function ($post) {
                return [
                    'title' => $post->title,
                    'content' => Str::take($post->content, 100),
                    'created_at' => $post->created_at->diffForHumans(),
                    'read_time' => $post->read_time . ' min',
                    'cover_image' => $post->cover_image ?? null,
                    'id' => $post->id,
                    'note' => $post->pivot->note,

                    'author' => [
                        'name' => $post->user->name,
                        'avatar_url' => $post->user->avatar_url,
                    ],
                ];
            }),

        ];
    }
}
