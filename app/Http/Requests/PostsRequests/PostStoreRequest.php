<?php

namespace App\Http\Requests\PostsRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PostStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:500',
            'content' => 'required|string|max:7000',

            'image_url' => 'nullable|array|max:10',
            'image_url.*' => 'image|mimes:jpeg,png,jpg,jpeg,webp,gif|max:5120',

            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',

            'slug' => 'sometimes|string|unique:posts,slug',
            'status' => 'nullable|in:draft,published',
            'read_time' => 'nullable|integer|min:1|max:59',

            // Optional generated image ID for AI-generated cover images
            'generated_image_id' => [
                'nullable',
                'integer',
                Rule::exists('generated_post_images', 'id')
                    ->where(function ($query) {
                        $query->where('user_id', Auth::id())
                            ->where('status', 'pending');
                    }),
            ],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
