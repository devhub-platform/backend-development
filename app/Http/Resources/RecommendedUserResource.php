<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendedUserResource extends JsonResource
{
    private ?float $recommendationScore = null;
    private ?array $recommendationReasons = null;

    public function setRecommendationScore(float $score): self
    {
        $this->recommendationScore = $score;
        return $this;
    }

    public function setRecommendationReasons(array $reasons): self
    {
        $this->recommendationReasons = $reasons;
        return $this;
    }

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

            'email' => $this->when($this->isOwner($request), $this->email),

            'education' => $this->education,
            'work_at' => $this->work_at,
            'skills' => $this->skills ?? [],
            'currently_learning' => $this->currently_learning,

            'location' => $this->location,
            'website_url' => $this->website_url,

            'social_links' => $this->getSocialLinks(),

            'stats' => [
                'followers' => $this->followers()->count(),
                'following' => $this->following()->count(),
                'posts' => $this->posts()->count(),
                'questions' => $this->questions()->count(),
            ],

            'is_verified' => (bool)$this->email_verified_at,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),

            'joined' => $this->created_at?->diffForHumans(),
            'joined_at' => $this->created_at?->format('Y-m-d'),

            // Recommendation specific fields
            'recommendation' => $this->when(
                !is_null($this->recommendationScore),
                [
                    'score' => round($this->recommendationScore * 100, 2), // Percentage
                    'reasons' => $this->recommendationReasons ?? [],
                ]
            ),
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
}

