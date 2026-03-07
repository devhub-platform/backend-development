<?php

namespace App\Http\Requests\PostAI;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'generated_image_id' => ['required', 'integer', 'exists:generated_post_images,id'],
            'post_id'            => ['required', 'integer', 'exists:posts,id'],
        ];
    }
}
