<?php

namespace App\Http\Requests\AuthRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisteredRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'username' => ['nullable', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'avatar_url' => ['nullable'],
            'bio' => ['nullable'],
            'email' => ['required', 'email', 'max:254'],
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
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.max' => 'Email must not exceed 254 characters',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'email.unique' => 'Email is already Taken',
        ];
    }
}
