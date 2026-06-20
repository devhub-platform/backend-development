<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function rules(): array
    {
        $action = $this->route()?->getActionMethod();

        return [
            'avatar_url'  => [
                $action === 'uploadAvatarImage' ? 'required' : 'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:5120',
            ],
            'cover_image' => [
                $action === 'uploadCoverImage' ? 'required' : 'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:5120',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'avatar_url.required'  => 'An image file is required.',
            'avatar_url.image'     => 'The avatar must be a valid image file.',
            'avatar_url.mimes'     => 'Avatar must be in JPEG, PNG, JPG, GIF, or SVG format.',
            'avatar_url.max'       => 'Avatar size must not exceed 5MB.',

            'cover_image.required' => 'An image file is required.',
            'cover_image.image'    => 'The cover image must be a valid image file.',
            'cover_image.mimes'    => 'Cover image must be in JPEG, PNG, JPG, GIF, or SVG format.',
            'cover_image.max'      => 'Cover image size must not exceed 5MB.',
        ];
    }
}
