<?php

namespace App\Http\Requests\QuestionsRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VoteQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'vote_type' => 'required|in:upvote,downvote',
        ];
    }

    public function messages(): array
    {
        return [
            'vote_type.required' => 'Vote type is required',
            'vote_type.in' => 'Vote type must be either upvote or downvote',
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

