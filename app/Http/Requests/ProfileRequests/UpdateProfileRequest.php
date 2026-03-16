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
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|alpha_dash|unique:users,username,' . auth()->id(),
            'bio' => 'sometimes|nullable|string|max:1000',
            'email' => 'sometimes|email|max:255|unique:users,email,' . auth()->id(),
            'education' => 'sometimes|nullable|string|max:500',
            'work_at' => 'sometimes|nullable|string|max:500',
            'skills' => 'sometimes|nullable|array|max:1000',
            'location' => 'sometimes|nullable|string|max:300',
            'website_url' => 'sometimes|nullable|url|max:255',
            'pronouns' => 'sometimes|nullable|string|max:50|in:he/him,she/her,they/them,other',
            'linkedin_username' => 'sometimes|nullable|string|max:255',
            'github_username' => 'sometimes|nullable|string|max:255',
            'orcid_username' => 'sometimes|nullable|string|max:255',
//            'avatar' => 'sometimes|nullable|image|max:2048',
//            'cover_image' => 'sometimes|nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name must not exceed 255 characters.',
            'bio.max' => 'Bio must not exceed 1000 characters.',
            'location.max' => 'Location must not exceed 300 characters.',
            'website_url.url' => 'Website URL must be a valid URL.',
            'skills.array' => 'Skills must be an array.',
//            'avatar.image' => 'Avatar must be an image file.',
//            'avatar.max' => 'Avatar must not exceed 2MB.',
//            'cover_image.image' => 'Cover image must be an image file.',
//            'cover_image.max' => 'Cover image must not exceed 2MB.',
        ];
    }
}
