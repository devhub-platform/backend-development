<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'avatar_url.image' => 'The avatar must be a valid image file.',
            'avatar_url.mimes' => 'Avatar must be in JPEG, PNG, JPG, GIF, or SVG format.',
            'avatar_url.max' => 'Avatar size must not exceed 5MB.',

            'cover_image.image' => 'The cover image must be a valid image file.',
            'cover_image.mimes' => 'Cover image must be in JPEG, PNG, JPG, GIF, or SVG format.',
            'cover_image.max' => 'Cover image size must not exceed 5MB.',
        ];
    }
}
