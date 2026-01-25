<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ID' => $this->id,
            'Name' => $this->name,
            'Username' => $this->username,
            'Role' => $this->role ?? 'user',
            'Avatar Image' => $this->avatar_url ?? null,
            'Cover Image' => $this->cover_image ?? null,
            'Pronouns' => $this->pronouns ?? 'Not specified',
            'Bio' => $this->bio,
//            'Provider ID' => $this->provider_id,
            'Email' => $this->email,
            'Eduction' => $this->education ?? 'Not specified',
            'Work At' => $this->work_at ?? 'Not specified',
            'Skills' => $this->skills ? json_decode($this->skills, true) : [],
            'Location' => $this->location ?? 'Not specified',
            'Website URL' => $this->website_url ?? 'Not specified',

            'LinkedIn Username' => $this->linkedin_username ? 'https://www.linkedin.com/in/' . $this->linkedin_username : 'Not specified',
            'GitHub Username' => $this->github_username ? 'https://github.com/' . $this->github_username : 'Not specified',

            'Currently Learning' => $this->currently_learning ?? 'Not specified',
            'Email verified at' => $this->email_verified_at ? $this->email_verified_at->format('Y-m-d H:i:s') : null,
            'Join At' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'Last Update' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
