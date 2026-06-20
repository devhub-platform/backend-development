<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserEmbeddingService
{
    private const EMBEDDING_CACHE_PREFIX = 'user_embedding:';
    private const EMBEDDING_CACHE_MINUTES = 1440; // 24 hours

    /**
     * Generate user embedding vector based on profile and activity
     */
    public function generateUserEmbedding(User $user): array
    {
        // Check cache first
        $cacheKey = self::EMBEDDING_CACHE_PREFIX . $user->id;
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $embedding = [
            'skills_vector' => $this->getSkillsVector($user),
            'interests_vector' => $this->getInterestsVector($user),
            'activity_vector' => $this->getActivityVector($user),
            'social_vector' => $this->getSocialVector($user),
            'profile_completeness' => $this->getProfileCompleteness($user),
        ];

        // Cache the embedding
        Cache::put($cacheKey, $embedding, now()->addMinutes(self::EMBEDDING_CACHE_MINUTES));

        return $embedding;
    }

    /**
     * Calculate similarity score between two embeddings
     */
    public function calculateSimilarityScore(array $embedding1, array $embedding2): float
    {
        $skillsSimilarity = $this->cosineSimilarity(
            $embedding1['skills_vector'] ?? [],
            $embedding2['skills_vector'] ?? []
        );

        $interestsSimilarity = $this->cosineSimilarity(
            $embedding1['interests_vector'] ?? [],
            $embedding2['interests_vector'] ?? []
        );

        $activitySimilarity = $this->cosineSimilarity(
            $embedding1['activity_vector'] ?? [],
            $embedding2['activity_vector'] ?? []
        );

        $socialSimilarity = $this->cosineSimilarity(
            $embedding1['social_vector'] ?? [],
            $embedding2['social_vector'] ?? []
        );

        // Weighted average of similarities
        return (
            $skillsSimilarity * 0.35 +
            $interestsSimilarity * 0.35 +
            $activitySimilarity * 0.20 +
            $socialSimilarity * 0.10
        );
    }

    /**
     * Get skills vector (normalized)
     */
    private function getSkillsVector(User $user): array
    {
        $skills = is_array($user->skills) ? $user->skills : [];

        if (empty($skills)) {
            return array_fill(0, 10, 0);
        }

        // Create a fixed-size vector (top 10 skills)
        $vector = array_fill(0, 10, 0);
        foreach (array_slice($skills, 0, 10) as $index => $skill) {
            // Weight each skill by its position (earlier skills weighted higher)
            $vector[$index] = 1.0 - ($index * 0.05);
        }

        return $vector;
    }

    /**
     * Get interests vector from followed topics and tags
     */
    private function getInterestsVector(User $user): array
    {
        $vector = array_fill(0, 10, 0);

        // Get followed topics
        $topicsCount = $user->topics()->count();
        $tagsCount = $user->followedTags()->count();

        if ($topicsCount > 0) {
            $vector[0] = min($topicsCount / 5, 1.0); // Normalize to 0-1
        }

        if ($tagsCount > 0) {
            $vector[1] = min($tagsCount / 5, 1.0);
        }

        // Add currently learning as interest indicator
        if ($user->currently_learning) {
            $vector[2] = 0.8;
        }

        // Education level indicator
        if ($user->education) {
            $vector[3] = 0.6;
        }

        return $vector;
    }

    /**
     * Get activity vector based on user engagement
     */
    private function getActivityVector(User $user): array
    {
        $vector = array_fill(0, 10, 0);

        // Post count (normalized)
        $postCount = $user->posts()->count();
        $vector[0] = min($postCount / 20, 1.0);

        // Question count
        $questionCount = $user->questions()->count();
        $vector[1] = min($questionCount / 10, 1.0);

        // Answer count
        $answerCount = $user->answers()->count();
        $vector[2] = min($answerCount / 10, 1.0);

        $savedPostsCount = $user->savedPosts()->count();
        $vector[3] = min($savedPostsCount / 20, 1.0);

        // Email verification (active user indicator)
        $vector[4] = $user->email_verified_at ? 1.0 : 0.0;

        // Account age (in months)
        $monthsOld = $user->created_at->diffInMonths(now());
        $vector[5] = min($monthsOld / 24, 1.0); // Normalize to max 2 years

        return $vector;
    }

    /**
     * Get social vector based on followers/following
     */
    private function getSocialVector(User $user): array
    {
        $vector = array_fill(0, 10, 0);

        $followerCount = $user->followers()->count();
        $followingCount = $user->following()->count();

        // Followers (normalized)
        $vector[0] = min($followerCount / 50, 1.0);

        // Following (normalized)
        $vector[1] = min($followingCount / 50, 1.0);

        // Follower/Following ratio
        $ratio = $followingCount > 0 ? $followerCount / $followingCount : 0;
        $vector[2] = min($ratio / 2, 1.0);

        return $vector;
    }

    /**
     * Calculate profile completeness score
     */
    private function getProfileCompleteness(User $user): float
    {
        $completeness = 0;
        $fields = 0;

        $checkFields = [
            'bio',
            'avatar_url',
            'education',
            'skills',
            'location',
            'website_url',
            'linkedin_username',
            'github_username',
            'currently_learning',
        ];

        foreach ($checkFields as $field) {
            $fields++;
            if ($field === 'skills') {
                if (is_array($user->$field) && !empty($user->$field)) {
                    $completeness++;
                }
            } else {
                if ($user->$field) {
                    $completeness++;
                }
            }
        }

        return $completeness / $fields;
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosineSimilarity(array $vector1, array $vector2): float
    {
        if (empty($vector1) || empty($vector2)) {
            return 0.0;
        }

        // Pad vectors to same length
        $maxLen = max(count($vector1), count($vector2));
        $vector1 = array_pad($vector1, $maxLen, 0);
        $vector2 = array_pad($vector2, $maxLen, 0);

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < $maxLen; $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] ** 2;
            $magnitude2 += $vector2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Clear embedding cache for a user
     */
    public function clearCache(int $userId): void
    {
        Cache::forget(self::EMBEDDING_CACHE_PREFIX . $userId);
    }

    /**
     * Clear all embedding caches
     */
    public function clearAllCache(): void
    {
        // Note: In production, use a better approach like tagging
        Cache::tags(['embeddings'])->flush();
    }
}

