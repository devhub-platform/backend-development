<?php

namespace App\Http\Requests\PostsRequests;

use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_id' => ['sometimes', 'exists:users'],
            'title' => ['sometimes', 'required', 'string'],
            'content' => ['sometimes', 'required', 'string'],
            'slug' => ['sometimes', 'required', 'string', 'unique:posts,slug,' . $this->route('post')],
            'image_url' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
