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
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'role' => $this->role ?? 'user',
            'avatar_url' => $this->avatar_url ?? null,
            'cover_image' => $this->cover_image ?? null,
            'pronouns' => $this->pronouns ,
            'bio' => $this->bio,
            'email' => $this->email,
            'education' => $this->education ,
            'work_at' => $this->work_at ,
            'skills' => $this->skills ? json_decode($this->skills, true) : [],
            'location' => $this->location ,
            'website_url' => $this->website_url ,

            'linkedin_username' => $this->linkedin_username ? 'https://www.linkedin.com/in/' . $this->linkedin_username : null,
            'github_username' => $this->github_username ? 'https://github.com/' . $this->github_username : null,

            'currently_learning' => $this->currently_learning ?? 'Not specified',
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
