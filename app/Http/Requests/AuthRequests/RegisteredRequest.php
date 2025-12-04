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
}
