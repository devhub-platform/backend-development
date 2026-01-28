<?php

namespace App\Http\Requests\PostsRequests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPostRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                Rule::in(array_keys(Report::REASONS)),
            ],
            'message' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please select a reason for reporting this post.',
            'reason.in' => 'Invalid report reason selected.',
            'message.max' => 'Additional details cannot exceed 1000 characters.',
        ];
    }
}
