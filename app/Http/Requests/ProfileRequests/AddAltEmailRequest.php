<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddAltEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alt_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'alt_email')->ignore($this->user()->id),
                Rule::unique('users', 'email'),
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->email) {
                        $fail('Alternative email cannot be the same as your primary email.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alt_email.required' => 'Alternative email is required.',
            'alt_email.email' => 'Please provide a valid email address.',
            'alt_email.unique' => 'This email is already in use.',
        ];
    }
}
