<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
