<?php

namespace App\Http\Requests\PostsRequests;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'              => 'required|string|max:255',
            'content'            => 'required|string|max:5000',
            'image_url'          => 'nullable|string|url',
            'cover_image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tags'               => 'nullable|array|max:10',
            'tags.*'             => 'string|max:50',
            'slug'               => 'sometimes|string|unique:posts,slug',
            'status'             => 'nullable|in:draft,published',
            'read_time'          => 'nullable|integer|min:1|max:59',
            'generated_image_id' => 'nullable|integer|exists:generated_post_images,id',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
