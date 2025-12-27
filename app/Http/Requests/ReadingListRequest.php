<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadingListRequest extends FormRequest{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users'],
'title' => ['required'],
'description' => ['nullable'],//
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
