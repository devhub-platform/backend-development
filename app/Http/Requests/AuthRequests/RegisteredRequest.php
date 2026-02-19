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
            'username' => ['nullable', 'string', 'min:3', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            "provider_id" => ['nullable', 'string', 'max:255'],
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
            'username.string' => 'Username must be a valid string',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username must not exceed 255 characters',
            'username.unique' => 'This username is already taken',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.max' => 'Email must not exceed 254 characters',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a valid string',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
