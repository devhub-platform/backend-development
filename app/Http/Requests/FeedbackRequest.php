<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:400',
            'message' => 'required|string|max:5000',
            'type' => 'nullable|string|in:bug,feature_request,improvement,other',
            'rating' => 'nullable|integer|min:1|max:5',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z|max:102400', // Max 10MB
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
