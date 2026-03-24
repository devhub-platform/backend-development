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
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',

            'image_url' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value)) {
                        if (filter_var($value, FILTER_VALIDATE_URL)) {
                            return;
                        }

                        $fail('The ' . $attribute . ' field must be a valid URL or an image file.');
                        return;
                    }

                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        if (str_starts_with((string)$value->getMimeType(), 'image/')) {
                            return;
                        }

                        $fail('The ' . $attribute . ' file must be an image.');
                        return;
                    }

                    $fail('The ' . $attribute . ' field must be a valid URL or an image file.');
                },
            ],

            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

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
