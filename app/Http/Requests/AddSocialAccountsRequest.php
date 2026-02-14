<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSocialAccountsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'linkedin_username' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'min:3',
            ],
            'github_username' => [
                'sometimes',
                'nullable',
                'string',
                'max:39',
                'min:1',
            ],
            'orcid_username' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'linkedin_username.min' => 'LinkedIn username must be at least 3 characters',
            'linkedin_username.max' => 'LinkedIn username cannot exceed 255 characters',

            'github_username.max' => 'GitHub username cannot exceed 39 characters',
            'github_username.min' => 'GitHub username must be at least 1 character',

            'orcid_username.max' => 'ORCID username cannot exceed 255 characters',
        ];
    }


    public function attributes(): array
    {
        return [
            'linkedin_username' => 'LinkedIn username',
            'github_username' => 'GitHub username',
            'orcid_username' => 'ORCID identifier',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
