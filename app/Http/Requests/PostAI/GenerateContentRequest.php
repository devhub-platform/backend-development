<?php

namespace App\Http\Requests\PostAI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'A prompt is required to generate content.',
            'prompt.min'      => 'Prompt must be at least 10 characters.',
            'prompt.max'      => 'Prompt must not exceed 1000 characters.',
        ];
    }
}
