<?php

namespace App\Http\Requests\MessagesRequests;

use Illuminate\Foundation\Http\FormRequest;

class SendVoiceMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/webm,audio/mp4,audio/aac,audio/x-m4a',
            ],
            'file_name' => ['nullable', 'string', 'max:255'],
            'duration_ms' => ['nullable', 'integer', 'min:1', 'max:3600000'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

