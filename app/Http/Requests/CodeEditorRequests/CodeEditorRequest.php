<?php

namespace App\Http\Requests\CodeEditorRequests;

use Illuminate\Foundation\Http\FormRequest;

class CodeEditorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10000',
                'min:1'
            ],
            'language' => [
                'required',
                'string',
                'max:45',
                'min:1',
                'regex:/^[a-zA-Z0-9\-_\+#]+$/' // Allow language names with common special chars
            ],
            'version' => [
                'required',
                'string',
                'max:30',
                'min:1',
                'regex:/^[a-zA-Z0-9\.\-_]+$/' // Validate version format
            ],
            'timeout' => [
                'nullable',
                'integer',
                'between:1,35' // Max 35 seconds
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Code content is required',
            'code.max' => 'Code must not exceed 10000 characters',
            'code.min' => 'Code must contain at least 1 character',
            'language.required' => 'Programming language is required',
            'language.regex' => 'Language name format is invalid',
            'version.required' => 'Runtime version is required',
            'version.regex' => 'Version format is invalid',
            'timeout.between' => 'Timeout must be between 1 and 35 seconds',
        ];
    }

    /**
     * Authorize the request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from code
        if ($this->has('code')) {
            $this->merge([
                'code' => trim($this->code)
            ]);
        }

        // Normalize language name to lowercase
        if ($this->has('language')) {
            $this->merge([
                'language' => strtolower(trim($this->language))
            ]);
        }

        // Set default timeout if not provided
        if (!$this->has('timeout')) {
            $this->merge([
                'timeout' => 30
            ]);
        }
    }
}
