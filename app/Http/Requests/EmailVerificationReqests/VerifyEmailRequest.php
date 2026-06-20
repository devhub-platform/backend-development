<?php

namespace App\Http\Requests\EmailVerificationReqests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
