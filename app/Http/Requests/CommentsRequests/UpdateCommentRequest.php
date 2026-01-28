<?php

namespace App\Http\Requests\CommentsRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1500'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
