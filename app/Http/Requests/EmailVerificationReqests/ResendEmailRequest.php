<?php

namespace App\Http\Requests\EmailVerificationReqests;

use Illuminate\Foundation\Http\FormRequest;

class ResendEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
