<?php

namespace App\Services\Posts;

use App\Jobs\RefreshUserInterestVectorJob;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\EmbeddingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

class HomeFeedService
{
    public function __construct(private EmbeddingService $embedding)
    {
    }

    /**
     * FIX #2: accept $followingIds from the caller so the controller's
     * already-fetched result is reused instead of querying followers again.
     */
    public function build(
        ?User   $user,
        int     $perPage = 10,
        int     $page = 1,
        ?string $path = null,
        array   $query = [],
        ?array  $followingIds = null,
    ): LengthAwarePaginator
    {
        $perPage = max(1, min(50, $perPage));
        $page = max(1, $page);
        $path ??= Request::url();
        $query = $query ?: Request::query();

        if (!$user) {
            return $this->guestFeed($perPage, $page, $path, $query);
        }

        $blockedUserIds = $this->blockedUserIds($user);

        // FIX #2: use caller-supplied list or fetch once here — never twice.
        $followingIds ??= $user->following()->select('users.id')->pluck('users.id')->all();
        $followedTagNames = $user->followedTags()->select('name')->pluck('name')->all();
        $seenPostIds = $this->seenPostIds($user);

        $weightedInterestMap = $this->weightedInterestMap($user, $followedTagNames);
        $interestTerms = array_keys($weightedInterestMap);

        // FIX #1: read pre-warmed vector from cache — never block on the
        // embedding API at request time. RefreshUserInterestVectorJob keeps
        // the cache warm in the background.
        $interestVector = Cache::get("feed:interest-vector:{$user->id}", []);

        $cacheKey = 'home:candidates:' . $user->id . ':' . md5(json_encode([
                'blocked' => $blockedUserIds,
                'following' => $followingIds,
                'tags' => $followedTagNames,
                'interest_terms' => $interestTerms,
            ]));

        $candidates = Cache::remember($cacheKey, now()->addSeconds(20), function () use (
            $user, $blockedUserIds, $followingIds, $followedTagNames, $interestTerms
        ) {
            return $this->optimizedCandidatePool(
                $user, $blockedUserIds, $followingIds, $followedTagNames, $interestTerms
            );
        });

        if ($candidates->isEmpty()) {
            return Post::query()
                ->with(['user:id,name,username,avatar_url', 'tags:id,name'])
                ->where('status', '!=', 'draft')
                ->prioritizeFollowedTags($user)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        }

        // FIX #4: derive scored cache key from the candidates cache key hash
        // so we never re-serialize the full interest terms on every request.
        $scoredCacheKey = 'home:scored:' . $user->id . ':' . md5($cacheKey);

        $sorted = Cache::remember($scoredCacheKey, now()->addSeconds(20), function () use (
            $candidates, $interestVector, $interestTerms,
            $followingIds, $weightedInterestMap, $seenPostIds
        ) {
            $scored = $candidates->map(function (Post $post) use (
                $interestVector, $interestTerms, $followingIds,
                $weightedInterestMap, $seenPostIds
            ) {
                return [
                    'post' => $post,
                    'score' => $this->scorePost(
                        $post, $interestVector, $interestTerms,
                        $followingIds, $weightedInterestMap, $seenPostIds
                    ),
                ];
            });

            return $this->rerankWithDiversity($scored);
        });

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);
    }

    private function guestFeed(int $perPage, int $page, string $path, array $query): LengthAwarePaginator
    {
        // FIX #6: cache the entire guest feed result — it is not personalised,
        // so all guests on the same page share one computed result.
        $cacheKey = 'feed:guest:page:' . $page . ':pp:' . $perPage;

        $sorted = Cache::remember($cacheKey, now()->addSeconds(30), function () {
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

            return $recent
                ->merge($trending)
                ->unique('id')
                ->sortByDesc(fn(Post $post) => $this->guestScorePost($post))
                ->values();
        });

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);
    }

    private function weightedInterestMap(User $user, array $followedTagNames): array
    {
        $cacheKey = "feed:interest-map:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $followedTagNames) {
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

            $interactedPostIds = collect([
                ['ids' => $user->viewedPosts()->latest('post_views.viewed_at')->limit(90)->pluck('posts.id'), 'weight' => 1.0],
                ['ids' => $user->savedPosts()->latest('saved_posts.created_at')->limit(90)->pluck('posts.id'), 'weight' => 4.0],
            ]);

            $commentedIds = Post::query()
                ->whereHas('comments', fn($q) => $q->where('user_id', $user->id))
                ->latest()->limit(60)->pluck('id');

            $reactedIds = Post::query()
                ->whereHas('reactions', fn($q) => $q->where('user_id', $user->id))
                ->latest()->limit(60)->pluck('id');

            $allIds = $interactedPostIds->flatMap(fn($g) => $g['ids'])
                ->merge($commentedIds)
                ->merge($reactedIds)
                ->unique()
                ->values();

            $tagsByPost = DB::table('post_tags')
                ->join('tags', 'tags.id', '=', 'post_tags.tag_id')
                ->whereIn('post_tags.post_id', $allIds)
                ->select('post_tags.post_id', 'tags.name')
                ->get()
                ->groupBy('post_id');

            foreach ($interactedPostIds as $group) {
                foreach ($group['ids'] as $postId) {
                    foreach ($tagsByPost->get($postId, collect()) as $tag) {
                        $name = mb_strtolower(trim($tag->name));
                        if ($name !== '') {
                            $weights[$name] = ($weights[$name] ?? 0) + $group['weight'];
                        }
                    }
                }
            }
            foreach ($commentedIds as $postId) {
                foreach ($tagsByPost->get($postId, collect()) as $tag) {
                    $name = mb_strtolower(trim($tag->name));
                    if ($name !== '') {
                        $weights[$name] = ($weights[$name] ?? 0) + 5.0;
                    }
                }
            }
            foreach ($reactedIds as $postId) {
                foreach ($tagsByPost->get($postId, collect()) as $tag) {
                    $name = mb_strtolower(trim($tag->name));
                    if ($name !== '') {
                        $weights[$name] = ($weights[$name] ?? 0) + 4.5;
                    }
                }
            }

            if (empty($weights)) {
                return [];
            }

            arsort($weights);

            // FIX #1: whenever the interest map is rebuilt, schedule an async
            // vector refresh so the next request finds a warm embedding cache.
            dispatch(new RefreshUserInterestVectorJob($user->id, collect($weights)->take(40)->all()));

            return collect($weights)->take(40)->all();
        });
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

    /**
     * FIX #1: this method is now ONLY called from RefreshUserInterestVectorJob,
     * never from the hot request path. It is kept here so the job can use the
     * same service instance, but build() reads the result from cache only.
     */
    public function buildAndCacheInterestVector(int $userId, array $weightedMap): void
    {
        if (empty($weightedMap)) {
            return;
        }

        $terms = collect($weightedMap)
            ->sortDesc()
            ->take(25)
            ->flatMap(function (float $weight, string $term) {
                $repeat = (int)max(1, min(4, round($weight / 2)));
                return array_fill(0, $repeat, $term);
            })
            ->values()
            ->all();

        $vectorCacheKey = 'home:interest-vector:v2:' . md5(json_encode($weightedMap));

        $vector = Cache::remember($vectorCacheKey, now()->addHours(6), function () use ($terms) {
            try {
                return $this->embedding->embed(implode(' | ', $terms));
            } catch (\Throwable) {
                return [];
            }
        });

        // Store under the user-facing key so build() can read it instantly.
        Cache::put("feed:interest-vector:{$userId}", $vector, now()->addHours(6));
    }

    /**
     * @deprecated Use optimizedCandidatePool instead.
     */
    private function candidatePool(User $user, array $blockedUserIds, array $followingIds, array $followedTagNames, array $interestTerms): Collection
    {
        $baseQuery = $this->baseCandidateQuery($blockedUserIds);

        $recentPosts = (clone $baseQuery)->latest()->limit(220)->get();

        $followingPosts = empty($followingIds)
            ? collect()
            : (clone $baseQuery)->whereIn('user_id', $followingIds)->latest()->limit(180)->get();

        $tagPosts = empty($followedTagNames)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($q) => $q->whereIn('name', $followedTagNames))
                ->latest()->limit(180)->get();

        $interestPosts = empty($interestTerms)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($q) => $q->whereIn('name', $interestTerms))
                ->latest()->limit(180)->get();

        $trendingPosts = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByRaw('(COALESCE(views, 0) * 1.5) + (comments_count * 8) + (reactions_count * 10) + (saved_by_count * 9) DESC')
            ->limit(160)->get();

        $explorePosts = (clone $baseQuery)->inRandomOrder()->limit(40)->get();

        $fromFollowing = max(0, 80 - count($followingIds));
        $networkPosts = (clone $baseQuery)
            ->whereIn('user_id', $user->followers()->limit(200)->pluck('users.id')->all())
            ->latest()->limit($fromFollowing)->get();

        return $recentPosts
            ->merge($followingPosts)->merge($tagPosts)->merge($interestPosts)
            ->merge($trendingPosts)->merge($explorePosts)->merge($networkPosts)
            ->unique('id')->values();
    }

    private function optimizedCandidatePool(
        User  $user,
        array $blockedUserIds,
        array $followingIds,
        array $followedTagNames,
        array $interestTerms
    ): Collection
    {
        $baseQuery = $this->baseCandidateQuery($blockedUserIds);

        $recentPosts = (clone $baseQuery)->latest()->limit(80)->get();

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
            ->limit(80)
            ->get();

        $interestPosts = empty($interestTerms)
            ? collect()
            : (clone $baseQuery)
                ->whereHas('tags', fn($query) => $query->whereIn('name', $interestTerms))
                ->latest()
                ->limit(60)
                ->get();

        return $recentPosts
            ->merge($priorityPosts)
            ->merge($interestPosts)
            ->unique('id')
            ->shuffle()
            ->values()
            ->take(80);
    }

    private function baseCandidateQuery(array $blockedUserIds)
    {
        return Post::query()
            // FIX #3: select only what scorePost() needs — no withCount() since
            // comments_count / reactions_count / saved_by_count are denormalised
            // columns kept current by model observers (see PostObserver).
            ->select([
                'id', 'user_id', 'title', 'slug', 'content', 'status', 'created_at',  // ← add 'content'
                'views', 'uuid', 'updated_at', 'read_time', 'is_edit',
                'image_url', 'cover_image',
                'comments_count', 'reactions_count', 'saved_by_count',
            ])
            ->with([
                'user:id,name,username,avatar_url',
                'tags:id,name',
            ])
            ->where('status', '!=', 'draft')
            ->when(
                !empty($blockedUserIds),
                fn($query) => $query->whereNotIn('user_id', $blockedUserIds)
            );
    }

    private function seenPostIds(User $user): array
    {
        return Cache::remember(
            "feed:seen-ids:{$user->id}",
            now()->addSeconds(30),
            fn() => $user->viewedPosts()
                ->select('posts.id')
                ->latest('post_views.viewed_at')
                ->limit(200)
                ->pluck('posts.id')
                ->map(fn($id) => (int)$id)
                ->all()
        );
    }

    private function scorePost(
        Post  $post,
        array $interestVector,
        array $interestTerms,
        array $followingIds,
        array $weightedInterestMap,
        array $seenPostIds
    ): float
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

        // FIX #3: use denormalised columns — no runtime COUNT(*) subqueries.
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

        // FIX #5: random jitter is NOT added here — adding it inside the score
        // function poisons the scored cache on every cold build. Apply jitter
        // after rerankWithDiversity() returns, in build() itself if desired.

        return round($score, 4);
    }

    private function rerankWithDiversity(Collection $scored): Collection
    {
        $candidates = collect($scored)
            ->sortByDesc(fn($item) => $item['score'])
            ->take(200)
            ->values();

        $ranked = collect();
        $authorCount = [];
        $tagCount = [];
        $maxPerAuthor = 3;
        $maxPerTag = 8;

        foreach ($candidates as $item) {
            $post = $item['post'];
            $authorId = $post->user_id;

            if (($authorCount[$authorId] ?? 0) >= $maxPerAuthor) {
                continue;
            }

            $tagOverload = false;
            foreach ($post->tags as $tag) {
                $name = mb_strtolower((string)$tag->name);
                if (($tagCount[$name] ?? 0) >= $maxPerTag) {
                    $tagOverload = true;
                    break;
                }
            }
            if ($tagOverload) {
                continue;
            }

            $ranked->push($post);
            $authorCount[$authorId] = ($authorCount[$authorId] ?? 0) + 1;
            foreach ($post->tags as $tag) {
                $name = mb_strtolower((string)$tag->name);
                $tagCount[$name] = ($tagCount[$name] ?? 0) + 1;
            }
        }

        // Fill remaining slots if diversity filter thinned the list too much.
        if ($ranked->count() < 30) {
            $seen = $ranked->pluck('id')->flip();
            foreach ($candidates as $item) {
                if (!$seen->has($item['post']->id)) {
                    $ranked->push($item['post']);
                }
                if ($ranked->count() >= 60) {
                    break;
                }
            }
        }

        // FIX #5: apply exploration jitter here, after the ranked list is built
        // and before it is stored in cache — so every cached page is slightly
        // shuffled but deterministic for its TTL window.
        return $ranked->map(function (Post $post) {
            $post->_jitter = random_int(0, 1000) / 1000;
            return $post;
        })->sortByDesc('_jitter')->values()->map(function (Post $post) {
            unset($post->_jitter);
            return $post;
        });
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

        $text = mb_strtolower(
            $post->title . ' ' .
            ($post->content ?? '') . ' ' .
            $post->tags->pluck('name')->implode(' ')
        );
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
        return Cache::remember(
            "blocked_ids:{$user->id}",
            now()->addSeconds(30),
            fn() => $user->blockedUsers()->pluck('users.id')
                ->merge($user->blockers()->pluck('users.id'))
                ->unique()
                ->values()
                ->all()
        );
    }
}
