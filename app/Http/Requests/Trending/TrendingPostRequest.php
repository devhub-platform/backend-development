<?php

namespace App\Http\Requests\Trending;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TrendingPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tag_id'   => 'nullable|integer|exists:tags,id',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'tag_id.exists'    => 'The specified tag does not exist.',
            'tag_id.integer'   => 'tag_id must be an integer.',
            'per_page.integer' => 'per_page must be an integer.',
            'per_page.min'     => 'per_page must be at least 1.',
            'per_page.max'     => 'per_page cannot exceed 50.',
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
