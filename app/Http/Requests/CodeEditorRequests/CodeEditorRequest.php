<?php

namespace App\Http\Requests\CodeEditorRequests;

use Illuminate\Foundation\Http\FormRequest;

class CodeEditorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:500',
            'language' => 'required|string|max:45',
            'version' => 'required|string|max:30',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
