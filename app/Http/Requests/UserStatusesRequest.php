<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStatusesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'emoji' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[\x{1F300}-\x{1F9FF}]$/u', // Unicode emoji validation
            ],
            'status_text' => [
                'nullable',
                'string',
                'max:150',
                'min:1',
            ],
            'is_busy' => [
                'nullable',
                'boolean',
            ],
            'clear_after' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'emoji.regex' => 'The emoji must be a valid Unicode emoji character.',
            'status_text.max' => 'The status text may not be greater than 150 characters.',
            'status_text.min' => 'The status text must be at least 1 character.',
            'clear_after.after_or_equal' => 'The clear after date must be in the future.',
            'clear_after.date_format' => 'The clear after field must be a valid date format (Y-m-d H:i:s or Y-m-d).',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Allow at least one of the fields to be provided
        if (!$this->filled('emoji') && !$this->filled('status_text') && !$this->filled('is_busy')) {
            // Allow empty status update (for clearing status)
            if (!$this->filled('clear_after')) {
                // This will be validated separately if needed
            }
        }
    }
}
