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

    /**
     * FIX: Same as StoreQuestionRequest — decode tags if sent as JSON string.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('tags') && is_string($this->tags)) {
            $decoded = json_decode($this->tags, true);
            if (is_array($decoded)) {
                $this->merge(['tags' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title'          => 'sometimes|required|string|min:10|max:200',
            'content'        => 'sometimes|required|string|min:20|max:5000',
            'post_id'        => 'nullable|exists:posts,id',
            'tags'           => 'nullable|array|max:5',
            'tags.*'         => 'string|max:50',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_images'  => 'nullable|array',
            'remove_images.*'=> 'integer|exists:question_images,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.min'              => 'Title must be at least 10 characters',
            'title.max'              => 'Title cannot exceed 200 characters',
            'content.min'            => 'Content must be at least 20 characters',
            'content.max'            => 'Content cannot exceed 5000 characters',
            'tags.max'               => 'You can add up to 5 tags only',
            'tags.*.max'             => 'Each tag cannot exceed 50 characters',
            'images.max'             => 'You can upload up to 5 images only',
            'images.*.mimes'         => 'Images must be jpeg, png, jpg, gif, or webp',
            'images.*.max'           => 'Each image cannot exceed 5MB',
            'remove_images.*.exists' => 'One or more image IDs are invalid',
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
