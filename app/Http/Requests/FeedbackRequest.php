<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'nullable|string|in:bug,feature_request,improvement,other',
            'rating' => 'nullable|integer|min:1|max:5',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string|url',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
