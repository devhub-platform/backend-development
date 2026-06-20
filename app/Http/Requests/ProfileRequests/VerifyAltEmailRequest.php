<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAltEmailRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }


    public function messages(): array
    {
        return [
            'otp.required' => 'OTP code is required.',
            'otp.digits'   => 'OTP must be a 6-digit number.',
        ];
    }
}
