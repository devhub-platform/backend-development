<?php

namespace App\Http\Requests\AnswersRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:10|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Answer content is required',
            'content.min' => 'Content must be at least 10 characters',
            'content.max' => 'Content cannot exceed 5000 characters',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

