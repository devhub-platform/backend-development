<?php

namespace App\Http\Requests\CodeEditorRequests;

use Illuminate\Foundation\Http\FormRequest;

class CodeEditorRequest extends FormRequest
{

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
                'min:1'
            ],
            'version' => [
                'required',
                'string',
                'max:30',
                'min:1'
            ],
            'timeout' => [
                'nullable',
                'integer',
                'between:1,35'
            ],
            'stdin' => [
                'nullable',
                'string',
                'max:5000'
            ],
        ];
    }

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


    public function authorize(): bool
    {
        return true;
    }


    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => trim($this->code)
            ]);
        }

        if ($this->has('language')) {
            $this->merge([
                'language' => strtolower(trim($this->language))
            ]);
        }

        if (!$this->has('timeout')) {
            $this->merge([
                'timeout' => 30
            ]);
        }
    }
}
