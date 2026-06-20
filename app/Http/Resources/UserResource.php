<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Redis;
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
            // 'cv_url' => $this->cv_url,

            'email' => $this->email,
            'alt_email' => $this->when($this->isOwner($request), $this->alt_email),
            'alt_email_verified' => $this->when($this->isOwner($request), (bool)$this->alt_email_verified_at),

            'education' => $this->education,
            'work_at' => $this->work_at,
            'skills' => $this->skills ?? [],
            'currently_learning' => $this->currently_learning,

            'location' => $this->location,
            'website_url' => $this->website_url,

            'status' => $this->isOnline() ? 'online' : 'offline',

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
            'linkedin' => $this->buildSocialLink($this->linkedin_username, 'https://www.linkedin.com/in'),
            'github' => $this->buildSocialLink($this->github_username, 'https://github.com'),
            'orcid' => $this->buildSocialLink($this->orcid_username, 'https://orcid.org'),
        ]);
    }

    private function buildSocialLink(?string $value, string $baseUrl): ?array
    {
        if (!$value) {
            return null;
        }

        // Backward-compatible: old records may store usernames, new records store full URLs.
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($value, PHP_URL_PATH), '/');
            $username = $path ? basename($path) : null;

            return [
                'username' => $username,
                'url' => $value,
            ];
        }

        return [
            'username' => $value,
            'url' => rtrim($baseUrl, '/') . '/' . ltrim($value, '/'),
        ];
    }

    private function isOwner(Request $request): bool
    {
        return $request->user()?->id === $this->id;
    }

    private function isOnline()
    {
        return Redis::exists("user-online:{$this->id}");
    }
}
