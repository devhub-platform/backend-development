<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:topics,name'],
            'description' => ['nullable'],
            'icon' => ['nullable'],
            'display_order' => ['required', 'integer'],
            'is_active' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }
}
