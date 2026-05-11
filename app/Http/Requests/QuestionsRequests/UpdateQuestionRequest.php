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
            'title'           => 'sometimes|required|string|min:10|max:200',
            'content'         => 'sometimes|required|string|min:20|max:5000',
            'tags'            => 'sometimes|array|max:5',
            'tags.*'          => 'string|max:50',
            'images'          => 'sometimes|array|max:5',
            'images.*'        => 'url|max:2048',
            'remove_images'   => 'sometimes|array',
            'remove_images.*' => 'integer|exists:question_images,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.min'              => 'Title must be at least 10 characters.',
            'title.max'              => 'Title cannot exceed 200 characters.',
            'content.min'            => 'Content must be at least 20 characters.',
            'content.max'            => 'Content cannot exceed 5000 characters.',
            'tags.max'               => 'You can add up to 5 tags only.',
            'tags.*.max'             => 'Each tag cannot exceed 50 characters.',
            'images.max'             => 'You can add up to 5 images only.',
            'images.*.url'           => 'Each image must be a valid URL.',
            'remove_images.*.exists' => 'One or more images to remove do not exist.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
