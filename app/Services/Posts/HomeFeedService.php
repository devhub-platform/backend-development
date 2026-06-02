<?php

namespace App\Services\Posts;

use App\Models\Post;
use App\Models\User;
use App\Services\AI\EmbeddingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class HomeFeedService
{
    public function __construct(private EmbeddingService $embedding)
    {
    }

    public function build(?User $user, int $perPage = 10, int $page = 1, ?string $path = null, array $query = []): LengthAwarePaginator
    {
        $perPage = max(1, min(50, $perPage));
        $page = max(1, $page);
        $path ??= Request::url();
        $query = $query ?: Request::query();

        if (!$user) {
            return $this->guestFeed($perPage, $page, $path, $query);
        }

        $blockedUserIds = $this->blockedUserIds($user);

        $followingIds = $user->following()->select('users.id')->pluck('users.id')->all();
        $followedTagNames = $user->followedTags()->select('name')->pluck('name')->all();
        $seenPostIds = $this->seenPostIds($user);

        $weightedInterestMap = $this->weightedInterestMap($user, $followedTagNames);
        $interestTerms = array_keys($weightedInterestMap);

        $interestVector = empty($interestTerms) ? [] : $this->interestVector($weightedInterestMap);

        $cacheKey = 'home:candidates:' . $user->id . ':' . md5(json_encode([
            'blocked' => $blockedUserIds,
            'following' => $followingIds,
            'tags' => $followedTagNames,
            'interest_terms' => $interestTerms,
        ]));

        // Cache candidate pool briefly to avoid repeated expensive DB queries
        $candidates = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($user, $blockedUserIds, $followingIds, $followedTagNames, $interestTerms) {
            return $this->optimizedCandidatePool($user, $blockedUserIds, $followingIds, $followedTagNames, $interestTerms);
        });

        if ($candidates->isEmpty()) {
            return Post::query()
                ->with(['user:id,name,username,avatar_url', 'tags:id,name'])
                ->where('status', '!=', 'draft')
                ->prioritizeFollowedTags($user)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        }

        $scored = $candidates->map(function (Post $post) use ($interestVector, $interestTerms, $followingIds, $weightedInterestMap, $seenPostIds) {
            return [
                'post' => $post,
                'score' => $this->scorePost($post, $interestVector, $interestTerms, $followingIds, $weightedInterestMap, $seenPostIds),
            ];
        });

        $sorted = $this->rerankWithDiversity($scored);

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);
    }

    private function guestFeed(int $perPage, int $page, string $path, array $query): LengthAwarePaginator
    {
        $baseQuery = $this->baseCandidateQuery([]);

        $recent = (clone $baseQuery)
            ->latest()
            ->limit(180)
            ->get();

        $trending = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByRaw('(COALESCE(views, 0) * 1.5) + (comments_count * 8) + (reactions_count * 10) + (saved_by_count * 9) DESC')
            ->limit(140)
            ->get();

        $sorted = $recent
            ->merge($trending)
            ->unique('id')
            ->sortByDesc(fn(Post $post) => $this->guestScorePost($post))
            ->values();

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);
    }

    private function weightedInterestMap(User $user, array $followedTagNames): array
    {
        $weights = [];

        foreach ($user->topics()->pluck('name')->filter()->all() as $topicName) {
            $topicName = mb_strtolower(trim((string)$topicName));
            if ($topicName !== '') {
                $weights[$topicName] = ($weights[$topicName] ?? 0) + 4.5;
            }
        }

        foreach ($followedTagNames as $tagName) {
            $tagName = mb_strtolower(trim((string)$tagName));
            if ($tagName !== '') {
                $weights[$tagName] = ($weights[$tagName] ?? 0) + 3.5;
            }
        }

        $this->addTagWeightsFromPosts(
            $user->viewedPosts()->with('tags')->latest('post_views.viewed_at')->limit(90)->get(),
            1.0,
            $weights
        );

        $this->addTagWeightsFromPosts(
            $user->savedPosts()->with('tags')->latest('saved_posts.created_at')->limit(90)->get(),
            4.0,
            $weights
        );

        $this->addTagWeightsFromPosts(
            Post::query()->with('tags')->whereHas('comments', fn($q) => $q->where('user_id', $user->id))->latest()->limit(60)->get(),
            5.0,
            $weights
        );

        $this->addTagWeightsFromPosts(
            Post::query()->with('tags')->whereHas('reactions', fn($q) => $q->where('user_id', $user->id))->latest()->limit(60)->get(),
            4.5,
            $weights
        );

        if (empty($weights)) {
            return [];
        }

        arsort($weights);

        return collect($weights)
            ->take(40)
            ->all();
    }

    private function addTagWeightsFromPosts(Collection $posts, float $weightPerTag, array &$weights): void
    {
        foreach ($posts as $post) {
            foreach ($post->tags as $tag) {
                $name = mb_strtolower(trim((string)$tag->name));
                if ($name === '') {
                    continue;
                }

                $weights[$name] = ($weights[$name] ?? 0) + $weightPerTag;
            }
        }
    }

    private function interestVector(array $weightedMap): array
    {
        if (empty($weightedMap)) {
            return [];
        }

        $cacheKey = 'home:interest-vector:v2:' . md5(json_encode($weightedMap));

        $terms = collect($weightedMap)
            ->sortDesc()
            ->take(25)
            ->flatMap(function (float $weight, string $term) {
                $repeat = (int)max(1, min(4, round($weight / 2)));

                return array_fill(0, $repeat, $term);
            })
            ->values()
            ->all();

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($terms) {
            return $this->embedding->embed(implode(' | ', $terms));
        });
    }

    private function candidatePool(User $user, array $blockedUserIds, array $followingIds, array $followedTagNames, array $interestTerms): Collection
    {
        $baseQuery = $this->baseCandidateQuery($blockedUserIds);

        $recentPosts = (clone $baseQuery)
            ->latest()
            ->limit(220)
            ->get();

        $followingPosts = empty($followingIds)
            ? collect()
            : (clone $baseQuery)
                ->whereIn('user_id', $followingIds)
                ->latest()
                ->limit(180)
                ->get();

        $tagPosts = empty($followedTagNames)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($query) => $query->whereIn('name', $followedTagNames))
                ->latest()
                ->limit(180)
                ->get();

        $interestPosts = empty($interestTerms)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($query) => $query->whereIn('name', $interestTerms))
                ->latest()
                ->limit(180)
                ->get();

        $trendingPosts = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByRaw('(COALESCE(views, 0) * 1.5) + (comments_count * 8) + (reactions_count * 10) + (saved_by_count * 9) DESC')
            ->limit(160)
            ->get();

        $explorePosts = (clone $baseQuery)
            ->inRandomOrder()
            ->limit(40)
            ->get();

        $fromFollowing = max(0, 80 - count($followingIds));
        $networkPosts = (clone $baseQuery)
            ->whereIn('user_id', $user->followers()->limit(200)->pluck('users.id')->all())
            ->latest()
            ->limit($fromFollowing)
            ->get();

        return $recentPosts
            ->merge($followingPosts)
            ->merge($tagPosts)
            ->merge($interestPosts)
            ->merge($trendingPosts)
            ->merge($explorePosts)
            ->merge($networkPosts)
            ->unique('id')
            ->values();
    }

    /**
     * Optimized candidate pool - reduces queries by combining results
     */
    private function optimizedCandidatePool(User $user, array $blockedUserIds, array $followingIds, array $followedTagNames, array $interestTerms): Collection
    {
        $baseQuery = $this->baseCandidateQuery($blockedUserIds);

        // Single query for recent posts instead of multiple (reduced limit)
        $recentPosts = (clone $baseQuery)
            ->latest()
            ->limit(120)
            ->get();

        // Combine following + trending in priority order
        $priorityPosts = (clone $baseQuery)
            ->where(function ($query) use ($followingIds, $followedTagNames) {
                if (!empty($followingIds)) {
                    $query->whereIn('user_id', $followingIds);
                }
                if (!empty($followedTagNames)) {
                    $query->orWhereHas('tags', fn($q) => $q->whereIn('name', $followedTagNames));
                }
            })
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByRaw('(COALESCE(views, 0) * 1.5) + (comments_count * 8) + (reactions_count * 10) DESC')
            ->limit(90)
            ->get();

        // Interest-based posts
        $interestPosts = empty($interestTerms)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($query) => $query->whereIn('name', $interestTerms))
                ->latest()
                ->limit(80)
                ->get();

        return $recentPosts
            ->merge($priorityPosts)
            ->merge($interestPosts)
            ->unique('id')
            ->shuffle()
            ->values();
    }

    private function baseCandidateQuery(array $blockedUserIds)
    {
        return Post::query()
            ->select('id', 'user_id', 'title', 'content', 'slug', 'status', 'created_at', 'views')
            ->with([
                'user:id,name,username,avatar_url',
                'tags:id,name'
            ])
            ->withCount(['comments', 'reactions', 'savedBy', 'postViews'])
            ->where('status', '!=', 'draft')
            ->when(!empty($blockedUserIds), fn($query) => $query->whereNotIn('user_id', $blockedUserIds));
    }

    private function seenPostIds(User $user): array
    {
        return $user->viewedPosts()
            ->select('posts.id')
            ->latest('post_views.viewed_at')
            ->limit(200)
            ->pluck('posts.id')
            ->map(fn($id) => (int)$id)
            ->all();
    }

    private function scorePost(Post $post, array $interestVector, array $interestTerms, array $followingIds, array $weightedInterestMap, array $seenPostIds): float
    {
        $score = 0.0;
        $postVector = $this->embedding->getCachedEmbedding($post);

        if (!empty($interestVector) && !empty($postVector)) {
            $score += $this->embedding->cosine($interestVector, $postVector) * 58;
        } else {
            $score += $this->keywordScore($post, $interestTerms);
        }

        if (in_array($post->user_id, $followingIds, true)) {
            $score += 16;
        }

        $tagAffinity = 0.0;
        foreach ($post->tags as $tag) {
            $tagName = mb_strtolower((string)$tag->name);
            $tagAffinity += (float)($weightedInterestMap[$tagName] ?? 0);
        }

        if ($tagAffinity > 0) {
            $score += min(24, $tagAffinity * 1.75);
        }

        $views = (int)($post->views ?? 0);
        $comments = (int)($post->comments_count ?? 0);
        $reactions = (int)($post->reactions_count ?? 0);
        $saved = (int)($post->saved_by_count ?? 0);
        $engagementScore =
            (log(1 + $views) * 1.8) +
            (log(1 + $comments) * 4.5) +
            (log(1 + $reactions) * 5.0) +
            (log(1 + $saved) * 4.0);
        $score += min(26, $engagementScore);

        $hoursOld = max(1, now()->diffInHours($post->created_at));
        $score += 14 * exp(-$hoursOld / 96);

        $isSeen = in_array((int)$post->id, $seenPostIds, true);
        $score += $isSeen ? -10 : 5;

        if (!empty($post->title)) {
            $titleLength = mb_strlen((string)$post->title);
            if ($titleLength >= 30 && $titleLength <= 140) {
                $score += 1.5;
            }
        }

        $score += random_int(0, 1000) / 1000;

        return round($score, 4);
    }

    private function rerankWithDiversity(Collection $scored): Collection
    {
        $remaining = $scored->values()->all();
        $ranked = collect();
        $authorCounts = [];
        $tagCounts = [];

        while (!empty($remaining)) {
            $bestIndex = null;
            $bestAdjusted = -INF;
            $bestTimestamp = 0;

            foreach ($remaining as $index => $item) {
                $post = $item['post'];
                $baseScore = (float)$item['score'];

                $authorPenalty = (($authorCounts[$post->user_id] ?? 0) * 7.0);

                $tagPenalty = 0.0;
                foreach ($post->tags as $tag) {
                    $name = mb_strtolower((string)$tag->name);
                    $tagPenalty += (($tagCounts[$name] ?? 0) * 1.8);
                }

                $adjusted = $baseScore - $authorPenalty - $tagPenalty;
                $timestamp = $post->created_at?->timestamp ?? 0;

                if ($adjusted > $bestAdjusted || ($adjusted === $bestAdjusted && $timestamp > $bestTimestamp)) {
                    $bestAdjusted = $adjusted;
                    $bestTimestamp = $timestamp;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex === null) {
                break;
            }

            $picked = $remaining[$bestIndex];
            $pickedPost = $picked['post'];
            $ranked->push($pickedPost);

            $authorCounts[$pickedPost->user_id] = ($authorCounts[$pickedPost->user_id] ?? 0) + 1;
            foreach ($pickedPost->tags as $tag) {
                $name = mb_strtolower((string)$tag->name);
                $tagCounts[$name] = ($tagCounts[$name] ?? 0) + 1;
            }

            unset($remaining[$bestIndex]);
            $remaining = array_values($remaining);
        }

        return $ranked;
    }

    private function guestScorePost(Post $post): float
    {
        $views = (int)($post->views ?? 0);
        $comments = (int)($post->comments_count ?? 0);
        $reactions = (int)($post->reactions_count ?? 0);
        $saved = (int)($post->saved_by_count ?? 0);

        $hoursOld = max(1, now()->diffInHours($post->created_at));
        $freshness = 18 * exp(-$hoursOld / 84);

        $engagement =
            (log(1 + $views) * 1.8) +
            (log(1 + $comments) * 4.5) +
            (log(1 + $reactions) * 5.0) +
            (log(1 + $saved) * 4.0);

        return round($freshness + min(30, $engagement), 4);
    }

    private function keywordScore(Post $post, array $interestTerms): float
    {
        if (empty($interestTerms)) {
            return 0.0;
        }

        $text = mb_strtolower($post->title . ' ' . ($post->content ?? '') . ' ' . $post->tags->pluck('name')->implode(' '));
        $score = 0.0;

        foreach ($interestTerms as $term) {
            $term = mb_strtolower(trim((string)$term));
            if ($term === '') {
                continue;
            }

            if (str_contains($text, $term)) {
                $score += 12;
                continue;
            }

            foreach (preg_split('/\s+/', $term) ?: [] as $word) {
                if (mb_strlen($word) > 2 && str_contains($text, $word)) {
                    $score += 3;
                }
            }
        }

        return $score;
    }

    private function blockedUserIds(User $user): array
    {
        return $user->blockedUsers()->pluck('users.id')
            ->merge($user->blockers()->pluck('users.id'))
            ->unique()
            ->values()
            ->all();
    }
}
