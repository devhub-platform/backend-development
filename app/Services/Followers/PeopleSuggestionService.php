<?php

namespace App\Services\Followers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;

class PeopleSuggestionService
{
    public function suggestForUser(User $user, int $limit = 10): Collection
    {
        $limit = max(1, min($limit, 20));

        $candidates = $this->baseCandidates($user, max($limit * 6, 30));
        if ($candidates->isEmpty()) {
            return $this->fillWithRandom($user, collect(), $limit);
        }

        $ranked = $this->rerankWithEmbeddings($user, $candidates);
        $ranked = $this->applyHybridScores($ranked);
        $selected = $ranked->take($limit)->values();

        if ($selected->count() < $limit) {
            return $this->fillWithRandom($user, $selected, $limit);
        }

        return $selected;
    }

    private function baseCandidates(User $user, int $poolSize): Collection
    {
        $excludeIds = $this->excludedUserIds($user);
        $topicIds = $user->topics()->pluck('topics.id');
        $followingIds = $user->following()->pluck('users.id');
        $followerIds = $user->followers()->pluck('users.id');

        return User::query()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('posts', function ($query) {
                $query->where('status', '!=', 'draft')
                    ->where('created_at', '>=', now()->subDays(180));
            })
            ->with('topics:id,name')
            ->withCount([
                'topics as shared_topics_count' => function ($query) use ($topicIds) {
                    if ($topicIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('topics.id', $topicIds);
                },
                'posts as published_posts_count' => function ($query) {
                    $query->where('status', '!=', 'draft');
                },
                'followers as mutual_followers_count' => function ($query) use ($followerIds) {
                    if ($followerIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('users.id', $followerIds);
                },
                'following as mutual_following_count' => function ($query) use ($followingIds) {
                    if ($followingIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('users.id', $followingIds);
                },
                'followers as followers_count',
                'following as following_count',
            ])
            ->withMax([
                'posts as latest_post_at' => function ($query) {
                    $query->where('status', '!=', 'draft');
                },
            ], 'created_at')
            ->orderByDesc('shared_topics_count')
            ->orderByDesc('mutual_followers_count')
            ->orderByDesc('mutual_following_count')
            ->orderByDesc('published_posts_count')
            ->orderByDesc('followers_count')
            ->orderByDesc('latest_post_at')
            ->limit($poolSize)
            ->get(['id', 'name', 'username', 'avatar_url', 'bio', 'skills', 'currently_learning', 'created_at']);
    }

    private function rerankWithEmbeddings(User $user, Collection $candidates): Collection
    {
        if (!config('services.hackai.embeddings_enabled', true)) {
            return $candidates;
        }

        $sourceText = $this->profileText($user, $user->topics()->pluck('topics.name')->all());
        if ($sourceText === '') {
            return $candidates;
        }

        $candidateTexts = $candidates
            ->mapWithKeys(fn(User $candidate) => [
                'user:' . $candidate->id => $this->profileText($candidate, $candidate->topics->pluck('name')->all()),
            ])
            ->all();

        $texts = array_merge(['source' => $sourceText], $candidateTexts);
        $embeddings = $this->resolveEmbeddings($texts);
        if (empty($embeddings) || empty($embeddings['source'] ?? [])) {
            return $candidates;
        }

        $sourceVector = $embeddings['source'];

        $scored = $candidates->values()->map(function (User $candidate) use ($sourceVector, $embeddings) {
            $candidateVector = $embeddings['user:' . $candidate->id] ?? [];
            $candidate->ai_similarity_score = $this->cosineSimilarity($sourceVector, $candidateVector);
            return $candidate;
        });

        return $scored->sort(function (User $left, User $right) {
            $scoreComparison = ($right->ai_similarity_score <=> $left->ai_similarity_score);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            $topicComparison = ((int)($right->shared_topics_count ?? 0)) <=> ((int)($left->shared_topics_count ?? 0));
            if ($topicComparison !== 0) {
                return $topicComparison;
            }

            $postComparison = ((int)($right->published_posts_count ?? 0)) <=> ((int)($left->published_posts_count ?? 0));
            if ($postComparison !== 0) {
                return $postComparison;
            }

            $followerComparison = ((int)($right->followers_count ?? 0)) <=> ((int)($left->followers_count ?? 0));
            if ($followerComparison !== 0) {
                return $followerComparison;
            }

            return $right->created_at <=> $left->created_at;
        })->values();
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
            ->inRandomOrder()
            ->limit($remaining)
            ->get(['id', 'name', 'username', 'avatar_url', 'bio']);

        return $selected->concat($fallback)->values();
    }

    private function excludedUserIds(User $user): array
    {
        $followingIds = $user->following()->pluck('users.id')->all();
        $blockedUserIds = $user->blockedUsers()->pluck('users.id')->all();
        $blockerIds = $user->blockers()->pluck('users.id')->all();

        return array_unique(array_merge($followingIds, $blockedUserIds, $blockerIds, [$user->id]));
    }

    private function applyHybridScores(Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $maxSharedTopics = max(1, (int) $candidates->max('shared_topics_count'));
        $maxMutualFollowers = max(1, (int) $candidates->max('mutual_followers_count'));
        $maxMutualFollowing = max(1, (int) $candidates->max('mutual_following_count'));

        return $candidates->map(function (User $candidate) use ($maxSharedTopics, $maxMutualFollowers, $maxMutualFollowing) {
            $semantic = (float) ($candidate->ai_similarity_score ?? 0.0);
            $semantic = max(0.0, min(1.0, ($semantic + 1.0) / 2.0));

            $sharedTopicsScore = ((int) ($candidate->shared_topics_count ?? 0)) / $maxSharedTopics;
            $mutualFollowersScore = ((int) ($candidate->mutual_followers_count ?? 0)) / $maxMutualFollowers;
            $mutualFollowingScore = ((int) ($candidate->mutual_following_count ?? 0)) / $maxMutualFollowing;
            $activityScore = $this->activityScore($candidate->latest_post_at);

            $candidate->suggestion_score = (
                (0.55 * $semantic) +
                (0.20 * $sharedTopicsScore) +
                (0.10 * $mutualFollowersScore) +
                (0.10 * $mutualFollowingScore) +
                (0.05 * $activityScore)
            );

            return $candidate;
        })->sort(function (User $left, User $right) {
            $scoreComparison = ($right->suggestion_score <=> $left->suggestion_score);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            $topicComparison = ((int) ($right->shared_topics_count ?? 0)) <=> ((int) ($left->shared_topics_count ?? 0));
            if ($topicComparison !== 0) {
                return $topicComparison;
            }

            return ($right->created_at <=> $left->created_at);
        })->values();
    }

    private function activityScore(mixed $latestPostAt): float
    {
        if (empty($latestPostAt)) {
            return 0.0;
        }

        try {
            $latest = Carbon::parse((string) $latestPostAt);
            $daysAgo = max(0, $latest->diffInDays(now()));
            return max(0.0, 1.0 - min(1.0, $daysAgo / 60));
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function resolveEmbeddings(array $texts): array
    {
        $model = (string) config('services.hackai.embeddings_model', 'openai/text-embedding-3-large');
        $cacheMinutes = (int) config('services.hackai.embeddings_cache_minutes', 720);

        $result = [];
        $missing = [];

        foreach ($texts as $key => $text) {
            if (!is_string($text) || trim($text) === '') {
                continue;
            }

            $cacheKey = $this->embeddingCacheKey($model, $text);
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && !empty($cached)) {
                $result[$key] = $cached;
                continue;
            }

            $missing[$key] = $text;
        }

        if (empty($missing)) {
            return $result;
        }

        try {
            $response = Embeddings::for(array_values($missing))
                ->timeout((int) config('services.hackai.embeddings_timeout', 20))
                ->generate('hackai', $model);

            $generated = $response->embeddings ?? [];
            if (count($generated) !== count($missing)) {
                return [];
            }

            $missingKeys = array_keys($missing);
            foreach ($missingKeys as $index => $key) {
                $vector = $generated[$index] ?? [];
                if (!is_array($vector) || empty($vector)) {
                    continue;
                }

                $text = $missing[$key];
                $cacheKey = $this->embeddingCacheKey($model, $text);
                Cache::put($cacheKey, $vector, now()->addMinutes($cacheMinutes));
                $result[$key] = $vector;
            }
        } catch (\Throwable $exception) {
            Log::warning('AI rerank failed for people suggestions, using fallback ranking.', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        return $result;
    }

    private function embeddingCacheKey(string $model, string $text): string
    {
        return 'people_suggestion_embedding:' . md5($model . '|' . $text);
    }

    private function profileText(User $user, array $topicNames): string
    {
        $skills = is_array($user->skills ?? null) ? implode(', ', $user->skills) : '';

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

        if ($leftNorm == 0.0 || $rightNorm == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($leftNorm) * sqrt($rightNorm));
    }
}
