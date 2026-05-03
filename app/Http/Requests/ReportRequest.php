<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|in:spam,harassment,hate_speech,violence,adult_content,copyright,misinformation,other',
            'message' => 'required_if:reason,other|string|max:1000',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
