<?php

namespace App\Http\Requests\ReadingListRequests;

use Illuminate\Foundation\Http\FormRequest;

class AddNoteToPostRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'The note field is required.',
            'note.string' => 'The note must be a string.',
            'note.max' => 'The note may not be greater than 1000 characters.',
        ];
    }
}

