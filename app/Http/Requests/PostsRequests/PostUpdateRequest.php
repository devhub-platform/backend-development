<?php

namespace App\Http\Requests\PostsRequests;

use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['sometimes', 'exists:users'],
            'title' => ['sometimes', 'string'],
            'content' => ['sometimes', 'string'],
            'slug' => ['sometimes', 'string', 'unique:posts,slug,' . $this->route('post')],
            'image_url' => ['sometimes', 'array', 'max:10'],
            'image_url.*' => ['image', 'mimes:jpeg,png,jpg,jpeg,webp,gif', 'max:5120'],
            'status' => ['sometimes', 'in:draft,published'],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
