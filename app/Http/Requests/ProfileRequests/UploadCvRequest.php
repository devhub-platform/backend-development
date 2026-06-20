<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cv' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:10000', // Max file size in KB (10MB)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cv.required' => 'A CV file is required.',
            'cv.file' => 'The CV must be a valid file.',
            'cv.mimes' => 'The CV must be a PDF, DOC, or DOCX file.',
            'cv.max' => 'The CV size must not exceed 5MB.',
        ];
    }
}
