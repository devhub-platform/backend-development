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
            'username' => ['required'],
            'password' => ['required', 'string', Password::defaults(),'confirmed'],
            'avatar_url' => ['nullable'],
            'bio' => ['nullable'],
            'email' => ['required', 'email', 'max:254'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
