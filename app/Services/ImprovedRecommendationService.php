<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ImprovedRecommendationService
{
    private const RECOMMENDATION_CACHE_PREFIX = 'recommendations:';
    private const CACHE_MINUTES = 60;

    public function __construct(
        private UserEmbeddingService $embeddingService
    ) {}

    /**
     * Get recommended users with multi-factor scoring
     */
    public function getRecommendedUsers(User $authUser, int $limit = 10, array $filters = []): Collection
    {
        $cacheKey = self::RECOMMENDATION_CACHE_PREFIX . $authUser->id . ':' . md5(json_encode($filters));

        $cached = Cache::get($cacheKey);
        if ($cached && !($filters['force_refresh'] ?? false)) {
            return collect($cached)->take($limit);
        }

        // Get excluded user IDs
        $excludedIds = $this->getExcludedUserIds($authUser);

        // Get candidate users
        $candidateUsers = User::whereNotIn('id', $excludedIds)
            ->where('id', '!=', $authUser->id)
            ->where('email_verified_at', '!=', null) // Only verified users
            ->limit($limit * 3) // Get more candidates for better filtering
            ->get();

        if ($candidateUsers->isEmpty()) {
            return collect();
        }

        // Get auth user embedding
        $authEmbedding = $this->embeddingService->generateUserEmbedding($authUser);

        // Score and rank candidates
        $scoredUsers = $candidateUsers->map(function (User $user) use ($authUser, $authEmbedding) {
            return [
                'user' => $user,
                'score' => $this->calculateUserRecommendationScore($user, $authUser, $authEmbedding),
            ];
        })->sortByDesc('score')
            ->take($limit)
            ->pluck('user');

        // Cache results
        Cache::put($cacheKey, $scoredUsers, now()->addMinutes(self::CACHE_MINUTES));

        return $scoredUsers;
    }

    /**
     * Calculate comprehensive recommendation score
     */
    private function calculateUserRecommendationScore(User $candidate, User $authUser, array $authEmbedding): float
    {
        $scores = [
            'embedding_similarity' => $this->getEmbeddingSimilarityScore($candidate, $authEmbedding),
            'mutual_connections' => $this->getMutualConnectionScore($candidate, $authUser),
            'activity_compatibility' => $this->getActivityCompatibilityScore($candidate, $authUser),
            'skills_match' => $this->getSkillsMatchScore($candidate, $authUser),
            'profile_quality' => $this->getProfileQualityScore($candidate),
        ];

        // Weighted scoring
        $finalScore = (
            $scores['embedding_similarity'] * 0.30 +
            $scores['mutual_connections'] * 0.25 +
            $scores['activity_compatibility'] * 0.20 +
            $scores['skills_match'] * 0.15 +
            $scores['profile_quality'] * 0.10
        );

        return $finalScore;
    }

    /**
     * Get embedding similarity score
     */
    private function getEmbeddingSimilarityScore(User $candidate, array $authEmbedding): float
    {
        $candidateEmbedding = $this->embeddingService->generateUserEmbedding($candidate);
        return $this->embeddingService->calculateSimilarityScore($authEmbedding, $candidateEmbedding);
    }

    /**
     * Calculate mutual connections score
     */
    private function getMutualConnectionScore(User $candidate, User $authUser): float
    {
        $authFollowerIds = $authUser->followers()->pluck('users.id')->toArray();
        $candidateFollowerIds = $candidate->followers()->pluck('users.id')->toArray();

        $mutualFollowers = count(array_intersect($authFollowerIds, $candidateFollowerIds));
        $maxMutual = max(count($authFollowerIds), count($candidateFollowerIds));

        return $maxMutual > 0 ? $mutualFollowers / $maxMutual : 0;
    }

    /**
     * Calculate activity compatibility score
     */
    private function getActivityCompatibilityScore(User $candidate, User $authUser): float
    {
        $authActivity = $authUser->posts()->count() + $authUser->questions()->count();
        $candidateActivity = $candidate->posts()->count() + $candidate->questions()->count();

        // Users with similar activity levels are more compatible
        $activityDiff = abs($authActivity - $candidateActivity);
        $maxActivity = max($authActivity, $candidateActivity, 1);

        return 1 - ($activityDiff / $maxActivity);
    }

    /**
     * Calculate skills match score
     */
    private function getSkillsMatchScore(User $candidate, User $authUser): float
    {
        $authSkills = is_array($authUser->skills) ? $authUser->skills : [];
        $candidateSkills = is_array($candidate->skills) ? $candidate->skills : [];

        if (empty($authSkills) || empty($candidateSkills)) {
            return 0;
        }

        $matchingSkills = count(array_intersect($authSkills, $candidateSkills));
        $totalSkills = count(array_unique(array_merge($authSkills, $candidateSkills)));

        return $matchingSkills / $totalSkills;
    }

    /**
     * Calculate profile quality score
     */
    private function getProfileQualityScore(User $user): float
    {
        $qualityFactors = 0;
        $maxFactors = 0;

        $checkFactors = [
            'avatar_url' => 1,
            'bio' => 1,
            'education' => 0.5,
            'skills' => 1,
            'location' => 0.5,
            'linkedin_username' => 0.5,
            'github_username' => 0.5,
        ];

        foreach ($checkFactors as $factor => $weight) {
            $maxFactors += $weight;
            if ($factor === 'skills') {
                if (is_array($user->$factor) && !empty($user->$factor)) {
                    $qualityFactors += $weight;
                }
            } else {
                if ($user->$factor) {
                    $qualityFactors += $weight;
                }
            }
        }

        return $maxFactors > 0 ? $qualityFactors / $maxFactors : 0;
    }

    /**
     * Get excluded user IDs (already following, blocked, etc.)
     */
    private function getExcludedUserIds(User $authUser): array
    {
        $alreadyFollowing = $authUser->following()->pluck('users.id')->toArray();
        $followers = $authUser->followers()->pluck('users.id')->toArray();
        $blocked = $authUser->blockedUsers()->pluck('users.id')->toArray();
        $blockers = $authUser->blockers()->pluck('users.id')->toArray();

        return array_unique(array_merge(
            $alreadyFollowing,
            $blocked,
            $blockers
        ));
    }

    /**
     * Clear cache for user
     */
    public function clearCache(User $user): void
    {
        $pattern = self::RECOMMENDATION_CACHE_PREFIX . $user->id . ':*';
        // For Redis, you would use: Cache::connection('redis')->getRedis()->eval(
        //     LUA_SCRIPT, 0, $pattern
        // );
        Cache::forget(self::RECOMMENDATION_CACHE_PREFIX . $user->id);
    }
}

