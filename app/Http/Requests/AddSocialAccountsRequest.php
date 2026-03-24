<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSocialAccountsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'linkedin_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?linkedin\.com\/.+/i',
            ],
            'github_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?github\.com\/.+/i',
            ],
            'orcid_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?orcid\.org\/.+/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'linkedin_url.url' => 'LinkedIn link must be a valid URL',
            'linkedin_url.max' => 'LinkedIn link cannot exceed 255 characters',
            'linkedin_url.regex' => 'LinkedIn link must be a linkedin.com URL',

            'github_url.url' => 'GitHub link must be a valid URL',
            'github_url.max' => 'GitHub link cannot exceed 255 characters',
            'github_url.regex' => 'GitHub link must be a github.com URL',

            'orcid_url.url' => 'ORCID link must be a valid URL',
            'orcid_url.max' => 'ORCID link cannot exceed 255 characters',
            'orcid_url.regex' => 'ORCID link must be an orcid.org URL',
        ];
    }


    public function attributes(): array
    {
        return [
            'linkedin_url' => 'LinkedIn URL',
            'github_url' => 'GitHub URL',
            'orcid_url' => 'ORCID URL',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
