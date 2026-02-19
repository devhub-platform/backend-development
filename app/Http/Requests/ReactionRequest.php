<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'max:50',
                'in:like,sad,love,angry,wow,haha',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Reaction type is required.',
            'type.in' => 'Invalid reaction type. Allowed types are: like, sad, love, angry, wow, haha.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
