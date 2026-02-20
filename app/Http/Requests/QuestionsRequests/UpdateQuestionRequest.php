<?php

namespace App\Http\Requests\QuestionsRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:10|max:200',
            'content' => 'sometimes|required|string|min:20|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Title must be at least 10 characters',
            'title.max' => 'Title cannot exceed 200 characters',
            'content.min' => 'Content must be at least 20 characters',
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

