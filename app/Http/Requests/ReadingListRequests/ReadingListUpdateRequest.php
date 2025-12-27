<?php

namespace App\Http\Requests\ReadingListRequests;

use Illuminate\Foundation\Http\FormRequest;

class ReadingListUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes'],
            'description' => ['nullable', 'sometimes'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
