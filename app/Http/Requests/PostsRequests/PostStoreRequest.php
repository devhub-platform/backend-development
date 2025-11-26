<?php

namespace App\Http\Requests\PostsRequests;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slug' => 'sometimes|string|unique:posts,slug',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
