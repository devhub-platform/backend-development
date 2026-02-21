<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string', 'max:500'],
            'location' => ['sometimes', 'string', 'max:255'],
            'website_url' => ['sometimes', 'url', 'max:255'],
            'pronouns' => ['sometimes', 'string', 'max:50'],
            'education' => ['sometimes', 'string', 'max:255'],
            'work_at' => ['sometimes', 'string', 'max:255'],
            'currently_learning' => ['sometimes', 'string', 'max:255'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name must not exceed 255 characters',
            'bio.max' => 'Bio must not exceed 500 characters',
            'location.max' => 'Location must not exceed 255 characters',
            'website_url.url' => 'Website URL must be a valid URL',
            'skills.array' => 'Skills must be an array',
        ];
    }
}

