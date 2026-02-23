<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'role' => $this->role ?? 'user',
            'pronouns' => $this->pronouns,
            'bio' => $this->bio,

            'avatar_url' => $this->avatar_url,
            'cover_image' => $this->cover_image,

            'email' => $this->email,
            'alt_email' => $this->when($this->isOwner($request), $this->alt_email),
            'alt_email_verified' => $this->when($this->isOwner($request), (bool)$this->alt_email_verified_at),

            'education' => $this->education,
            'work_at' => $this->work_at,
            'skills' => $this->skills ?? [],
            'currently_learning' => $this->currently_learning,

            'location' => $this->location,
            'website_url' => $this->website_url,

            'social_links' => $this->getSocialLinks(),

            'is_verified' => (bool)$this->email_verified_at,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),

            'joined' => $this->created_at?->diffForHumans(),
            'joined_at' => $this->created_at?->format('Y-m-d'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getSocialLinks(): array
    {
        return array_filter([
            'linkedin' => $this->linkedin_username
                ? ['username' => $this->linkedin_username, 'url' => "https://www.linkedin.com/in/{$this->linkedin_username}"]
                : null,
            'github' => $this->github_username
                ? ['username' => $this->github_username, 'url' => "https://github.com/{$this->github_username}"]
                : null,
            'orcid' => $this->orcid_username
                ? ['username' => $this->orcid_username, 'url' => "https://orcid.org/{$this->orcid_username}"]
                : null,
        ]);
    }

    private function isOwner(Request $request): bool
    {
        return $request->user()?->id === $this->id;
    }
}
