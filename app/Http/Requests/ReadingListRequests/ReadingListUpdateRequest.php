<?php

namespace App\Http\Requests\ReadingListRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingListUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('reading_lists')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id))
                    ->ignore($this->route('readingList')),
            ],
            'description' => ['nullable', 'sometimes', 'string', 'max:1000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'A reading list with this title already exists.',
        ];
    }
}
