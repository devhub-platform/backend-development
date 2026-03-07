<?php

namespace App\Http\Requests\PostAI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:10', 'max:500'],
            'model'  => ['nullable', 'string', 'in:google/gemini-2.5-flash-image,google/gemini-3.1-flash-image-preview'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'A prompt is required to generate an image.',
            'prompt.min'      => 'Prompt must be at least 10 characters.',
            'prompt.max'      => 'Prompt must not exceed 500 characters.',
            'model.in'        => 'Invalid model. Use google/gemini-2.5-flash-image or google/gemini-3.1-flash-image-preview.',
        ];
    }
}
