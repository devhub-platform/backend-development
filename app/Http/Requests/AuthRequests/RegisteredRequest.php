<?php

namespace App\Http\Requests\AuthRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisteredRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'email' => ['required', 'email:rfc,dns', 'max:254', 'unique:users,email'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.string' => 'Name must be a valid string',
            'name.max' => 'Name must not exceed 255 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.max' => 'Email must not exceed 254 characters',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a valid string',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match. Please ensure both passwords are identical',
        ];
    }
}
