<?php

namespace App\Http\Requests\AuthRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:254'],
            'password' => ['required', Password::defaults(), 'string'],
            'remember_me' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
