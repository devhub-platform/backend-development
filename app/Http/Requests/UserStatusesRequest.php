<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStatusesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'emoji' => ['nullable', 'string', 'max:10'],
            'status_text' => ['nullable', 'string', 'max:150'],
            'is_busy' => ['nullable', 'boolean'],
            'clear_after' => ['nullable', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
