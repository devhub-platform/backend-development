<?php

namespace App\Services\Followers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;

class PeopleSuggestionService
{
    public function suggestForUser(User $user, int $limit = 5): Collection
    {
        $limit = max(1, min($limit, 20));

        $candidates = $this->baseCandidates($user, max($limit * 6, 30));
        if ($candidates->isEmpty()) {
            return $this->fillWithRandom($user, collect(), $limit);
        }

        $ranked = $this->rerankWithEmbeddings($user, $candidates);
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

        return User::query()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('posts', function ($query) {
                $query->where('status', '!=', 'draft');
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
            ])
            ->orderByDesc('shared_topics_count')
            ->orderByDesc('published_posts_count')
            ->limit($poolSize)
            ->get(['id', 'name', 'username', 'avatar_url', 'bio', 'skills', 'currently_learning']);
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
            ->map(fn(User $candidate) => $this->profileText($candidate, $candidate->topics->pluck('name')->all()))
            ->all();

        try {
            $response = Embeddings::for(array_merge([$sourceText], $candidateTexts))
                ->timeout((int) config('services.hackai.embeddings_timeout', 20))
                ->generate('hackai', (string) config('services.hackai.embeddings_model', 'openai/text-embedding-3-large'));

            $embeddings = $response->embeddings;
        } catch (\Throwable $exception) {
            Log::warning('AI rerank failed for people suggestions, using fallback ranking.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return $candidates;
        }

        if (count($embeddings) !== count($candidateTexts) + 1) {
            return $candidates;
        }

        $sourceVector = $embeddings[0] ?? [];
        if (empty($sourceVector)) {
            return $candidates;
        }

        $scored = $candidates->values()->map(function (User $candidate, int $index) use ($sourceVector, $embeddings) {
            $candidate->ai_similarity_score = $this->cosineSimilarity($sourceVector, $embeddings[$index + 1] ?? []);
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

            return ((int)($right->published_posts_count ?? 0)) <=> ((int)($left->published_posts_count ?? 0));
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

        return array_merge($followingIds, [$user->id]);
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
