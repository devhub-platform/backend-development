<?php

namespace App\Http\Requests\MessagesRequests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageAttchmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
