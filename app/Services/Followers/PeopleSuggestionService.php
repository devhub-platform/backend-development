<?php

namespace App\Services\Followers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;

class PeopleSuggestionService
{
    private const SCORING_WEIGHTS = [
        'semantic' => 0.55,
        'shared_topics' => 0.20,
        'mutual_followers' => 0.10,
        'mutual_following' => 0.10,
        'activity' => 0.05,
    ];

    private const QUALITY_FILTERS = [
        'min_posts' => 1,
        'min_profile_completion' => 0.3,
        'active_days' => 180,
    ];

    private const CACHE_TTL = 1800;

    public function suggestForUser(User $user, int $limit = 10): Collection
    {
        $limit = max(1, min($limit, 50));

        try {
            $candidates = $this->baseCandidates($user, max($limit * 5, 50));

            if ($candidates->isEmpty()) {
                return $this->fillWithRandom($user, collect(), $limit);
            }

            $candidates = $this->applyAIReranking($user, $candidates);
            $scored = $this->applyHybridScores($candidates);
            $diverse = $this->boostDiversity($scored, $user);
            $selected = $diverse->take($limit)->values();

            if ($selected->count() < $limit) {
                return $this->fillWithRandom($user, $selected, $limit);
            }

            return $selected;
        } catch (\Throwable $exception) {
            Log::error('Suggestion service failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->fillWithRandom($user, collect(), $limit);
        }
    }

    private function baseCandidates(User $user, int $poolSize): Collection
    {
        $excludeIds = $this->excludedUserIds($user);
        $topicIds = $user->topics()->pluck('topics.id');
        $followingIds = $user->following()->pluck('users.id');
        $followerIds = $user->followers()->pluck('users.id');

        $query = User::query()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('posts', function ($query) {
                $query->where('status', '!=', 'draft')
                    ->where('created_at', '>=', now()->subDays(self::QUALITY_FILTERS['active_days']));
            })
            ->with(['topics:id,name'])
            ->withCount([
                'topics as shared_topics_count' => function ($q) use ($topicIds) {
                    if ($topicIds->isNotEmpty()) {
                        $q->whereIn('topics.id', $topicIds);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                },
                'posts as published_posts_count' => function ($q) {
                    $q->where('status', '!=', 'draft');
                },
                'followers as mutual_followers_count' => function ($q) use ($followerIds) {
                    if ($followerIds->isNotEmpty()) {
                        $q->whereIn('users.id', $followerIds);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                },
                'following as mutual_following_count' => function ($q) use ($followingIds) {
                    if ($followingIds->isNotEmpty()) {
                        $q->whereIn('users.id', $followingIds);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                },
                'followers as followers_count',
                'following as following_count',
            ])
            ->withMax('posts as latest_post_at', 'created_at', function ($q) {
                $q->where('status', '!=', 'draft');
            })
            ->selectRaw('
                users.*,
                CASE WHEN bio IS NOT NULL AND bio != \'\' THEN 0.25 ELSE 0 END +
                CASE WHEN avatar_url IS NOT NULL AND avatar_url != \'\' THEN 0.25 ELSE 0 END +
                CASE WHEN skills IS NOT NULL AND skills != \'\' THEN 0.25 ELSE 0 END +
                CASE WHEN currently_learning IS NOT NULL THEN 0.25 ELSE 0 END
                as profile_completeness
            ')
            ->having(DB::raw('profile_completeness'), '>=', self::QUALITY_FILTERS['min_profile_completion'])
            ->orderByDesc('shared_topics_count')
            ->orderByDesc('mutual_followers_count')
            ->orderByDesc('mutual_following_count')
            ->orderByDesc('followers_count')
            ->orderByDesc('latest_post_at')
            ->limit($poolSize)
            ->get([
                'id', 'name', 'username', 'avatar_url', 'bio', 'skills',
                'currently_learning', 'created_at', 'verified', 'is_featured'
            ]);

        return $query;
    }

    private function applyAIReranking(User $user, Collection $candidates): Collection
    {
        if (!config('services.hackai.embeddings_enabled', true)) {
            return $candidates;
        }

        try {
            $sourceText = $this->profileText($user, $user->topics()->pluck('topics.name')->all());

            if (empty($sourceText)) {
                return $candidates;
            }

            $candidateTexts = $candidates
                ->mapWithKeys(fn(User $c) => [
                    'user:' . $c->id => $this->profileText($c, $c->topics->pluck('name')->all()),
                ])
                ->all();

            $embeddings = $this->resolveEmbeddings($sourceText, $candidateTexts);

            if (empty($embeddings) || !isset($embeddings['source'])) {
                return $candidates;
            }

            $sourceVector = $embeddings['source'];

            return $candidates->map(function (User $candidate) use ($sourceVector, $embeddings) {
                $candidateVector = $embeddings['user:' . $candidate->id] ?? [];
                $candidate->ai_similarity_score = $this->cosineSimilarity($sourceVector, $candidateVector);
                return $candidate;
            })->sortByDesc('ai_similarity_score')->values();

        } catch (\Throwable $exception) {
            Log::warning('AI reranking failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return $candidates;
        }
    }

    private function applyHybridScores(Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $maxSharedTopics = max(1, (int)$candidates->max('shared_topics_count'));
        $maxMutualFollowers = max(1, (int)$candidates->max('mutual_followers_count'));
        $maxMutualFollowing = max(1, (int)$candidates->max('mutual_following_count'));

        return $candidates->map(function (User $candidate) use (
            $maxSharedTopics,
            $maxMutualFollowers,
            $maxMutualFollowing
        ) {
            $semantic = (float)($candidate->ai_similarity_score ?? 0.0);
            $semantic = max(0.0, min(1.0, ($semantic + 1.0) / 2.0));

            $topicScore = ((int)($candidate->shared_topics_count ?? 0)) / $maxSharedTopics;
            $mutualFollowersScore = ((int)($candidate->mutual_followers_count ?? 0)) / $maxMutualFollowers;
            $mutualFollowingScore = ((int)($candidate->mutual_following_count ?? 0)) / $maxMutualFollowing;
            $activityScore = $this->activityScore($candidate->latest_post_at);

            $presenceBonus = ($candidate->verified ? 0.05 : 0) + ($candidate->is_featured ? 0.03 : 0);

            $candidate->suggestion_score = (
                (self::SCORING_WEIGHTS['semantic'] * $semantic) +
                (self::SCORING_WEIGHTS['shared_topics'] * $topicScore) +
                (self::SCORING_WEIGHTS['mutual_followers'] * $mutualFollowersScore) +
                (self::SCORING_WEIGHTS['mutual_following'] * $mutualFollowingScore) +
                (self::SCORING_WEIGHTS['activity'] * $activityScore) +
                $presenceBonus
            );

            return $candidate;
        })
        ->sort(function (User $left, User $right) {
            $scoreComp = ($right->suggestion_score <=> $left->suggestion_score);
            if ($scoreComp !== 0) {
                return $scoreComp;
            }

            $topicComp = ((int)($right->shared_topics_count ?? 0)) <=> ((int)($left->shared_topics_count ?? 0));
            if ($topicComp !== 0) {
                return $topicComp;
            }

            return ($right->created_at <=> $left->created_at);
        })
        ->values();
    }

    private function boostDiversity(Collection $candidates, User $user): Collection
    {
        if ($candidates->count() <= 5) {
            return $candidates;
        }

        $diversified = collect();
        $selectedTopics = collect();
        $topicThreshold = 3;

        foreach ($candidates as $candidate) {
            $candidateTopics = $candidate->topics->pluck('id')->toArray();

            $topicOverlap = count(array_intersect(
                $selectedTopics->flatten()->toArray(),
                $candidateTopics
            ));

            if ($topicOverlap > $topicThreshold) {
                $candidate->suggestion_score *= 0.85;
            }

            $diversified->push($candidate);
            $selectedTopics->push($candidateTopics);
        }

        return $diversified->sort(function (User $left, User $right) {
            return ($right->suggestion_score <=> $left->suggestion_score);
        })->values();
    }

    private function activityScore(mixed $latestPostAt): float
    {
        if (empty($latestPostAt)) {
            return 0.0;
        }

        try {
            $latest = Carbon::parse((string)$latestPostAt);
            $daysAgo = max(0, $latest->diffInDays(now()));

            return max(0.0, exp(-$daysAgo / 30));
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function resolveEmbeddings(string $sourceText, array $candidateTexts): array
    {
        $model = config('services.hackai.embeddings_model', 'openai/text-embedding-3-large');
        $cacheMinutes = (int)config('services.hackai.embeddings_cache_minutes', 720);

        $result = [];
        $missing = [];

        $sourceCacheKey = $this->embeddingCacheKey($model, $sourceText);
        if ($cached = Cache::get($sourceCacheKey)) {
            $result['source'] = $cached;
        } else {
            $missing['source'] = $sourceText;
        }

        foreach ($candidateTexts as $key => $text) {
            if (!is_string($text) || trim($text) === '') {
                continue;
            }

            $cacheKey = $this->embeddingCacheKey($model, $text);
            if ($cached = Cache::get($cacheKey)) {
                $result[$key] = $cached;
            } else {
                $missing[$key] = $text;
            }
        }

        if (!empty($missing)) {
            try {
                $response = Embeddings::for(array_values($missing))
                    ->timeout((int)config('services.hackai.embeddings_timeout', 20))
                    ->generate('hackai', $model);

                $generated = $response->embeddings ?? [];

                if (count($generated) === count($missing)) {
                    $missingKeys = array_keys($missing);
                    foreach ($missingKeys as $index => $key) {
                        $vector = $generated[$index] ?? [];

                        if (is_array($vector) && !empty($vector)) {
                            $text = $missing[$key];
                            $cacheKey = $this->embeddingCacheKey($model, $text);
                            Cache::put($cacheKey, $vector, now()->addMinutes($cacheMinutes));
                            $result[$key] = $vector;
                        }
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('Embedding generation failed', [
                    'error' => $exception->getMessage(),
                    'model' => $model,
                ]);
            }
        }

        return $result;
    }

private function fillWithRandom(User $user, Collection $selected, int $limit): Collection
{
    if ($selected->count() >= $limit) {
        return $selected->take($limit)->values();
    }

    $remaining = $limit - $selected->count();
    $excludeIds = array_merge($this->excludedUserIds($user), $selected->pluck('id')->all());

    $fallback = User::query()
        ->whereNotIn('id', $excludeIds)
        ->where(function ($q) {                   // ← group the OR so whereNotIn isn't bypassed
            $q->where('is_featured', true);
        })
        ->orderByDesc('followers_count')
        ->inRandomOrder()
        ->limit($remaining)
        ->get(['id', 'name', 'username', 'avatar_url', 'bio']); // ← 'verified' removed

    if ($fallback->count() < $remaining) {
        $needMore = $remaining - $fallback->count();
        $excludeIds = array_merge($excludeIds, $fallback->pluck('id')->all());

        $more = User::query()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('posts')
            ->inRandomOrder()
            ->limit($needMore)
            ->get(['id', 'name', 'username', 'avatar_url', 'bio']);

        $fallback = $fallback->concat($more);
    }

    return $selected->concat($fallback)->values();
}

    private function excludedUserIds(User $user): array
    {
        return array_unique(array_merge(
            $user->following()->pluck('users.id')->all(),
            $user->blockedUsers()->pluck('users.id')->all(),
            $user->blockers()->pluck('users.id')->all(),
            [$user->id]
        ));
    }

    private function embeddingCacheKey(string $model, string $text): string
    {
        return 'suggestion_embedding:' . md5($model . '|' . $text);
    }

    private function profileText(User $user, array $topicNames): string
    {
        $skills = is_array($user->skills) ? implode(', ', $user->skills) : '';

        return trim(implode("\n", array_filter([
            $user->name,
            $user->bio,
            $skills,
            $user->currently_learning,
            implode(', ', $topicNames),
        ])));
    }

    private function cosineSimilarity(array $left, array $right): float
    {
        if (count($left) === 0 || count($left) !== count($right)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;

        foreach ($left as $index => $leftValue) {
            $rightValue = (float)$right[$index];
            $leftValue = (float)$leftValue;

            $dotProduct += $leftValue * $rightValue;
            $leftNorm += $leftValue ** 2;
            $rightNorm += $rightValue ** 2;
        }

        if ($leftNorm === 0.0 || $rightNorm === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($leftNorm) * sqrt($rightNorm));
    }
}
