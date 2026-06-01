<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Models\Tag;
use App\Services\AI\EmbeddingService;
use App\Services\AI\FeedMixer;
use App\Services\AI\TopicDetector;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrendingService
{
    private const CACHE_TTL            = 5;
    private const MAX_POSTS_PER_AUTHOR = 2;
    private const FETCH_LIMIT          = 50;

    public function __construct(
        private EmbeddingService $embedding,
        private TopicDetector    $topicDetector,
        private FeedMixer        $feedMixer,
    ) {}

    // ─── Public Entry Point ───────────────────────────────────────────────────

    public function getTrendingPosts(
        ?int $tagId   = null,
        int  $perPage = 10,
        int  $page    = 1,
    ): LengthAwarePaginator {

        // Cache the full pipeline result once per tag — not per page.
        // Pagination is done in PHP from the single cached array,
        // avoiding N separate cache entries for the same underlying data.
        $cacheKey = $tagId
            ? 'trending:posts:tag:' . $tagId
            : 'trending:posts:all';

        $allItems = Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL),
            fn() => $this->buildPipeline($tagId)
        );

        $total = count($allItems);
        $slice = array_slice($allItems, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    // ─── Pipeline ─────────────────────────────────────────────────────────────

    private function buildPipeline(?int $tagId): array
    {
        $posts = Post::query()
            ->select([
                'id', 'user_id', 'title', 'content',
                'views', 'created_at', 'cover_image',
                'image_url', 'embedded_at', 'embedding',
            ])
            ->where('status', 'published')
            ->with(['user:id,name,username,avatar_url', 'tags:id,name'])
            ->withCount(['comments', 'reactions'])
            ->when($tagId, fn($q) =>
            $q->whereHas('tags', fn($q) => $q->where('tags.id', $tagId))
            )
            ->orderByDesc('views')
            ->orderByDesc('created_at')
            ->limit(self::FETCH_LIMIT)
            ->get();

        if ($posts->isEmpty()) return [];

        // ── Step 1: Load cached embeddings ────────────────────────────────────
        $postsNeedingEmbedding = [];

        $prepared = $posts->map(function (Post $post) use (&$postsNeedingEmbedding) {
            $embedding = $this->embedding->getCachedEmbedding($post);

            if (empty($embedding)) {
                $postsNeedingEmbedding[] = $post->id;
            }

            return ['_model' => $post, 'embedding' => $embedding];
        })->values()->toArray();

        // Free the collection from memory
        unset($posts);

        // Embed missing posts in background AFTER response is sent
        if (!empty($postsNeedingEmbedding)) {
            defer(function () use ($postsNeedingEmbedding) {
                $posts = Post::whereIn('id', $postsNeedingEmbedding)->get();
                foreach ($posts as $post) {
                    $this->embedding->embedPost($post);
                }
            });
        }

        // ── Step 2: Score ─────────────────────────────────────────────────────
        $items = array_map(function ($item) use ($prepared) {
            $post      = $item['_model'];
            $embedding = $item['embedding'];

            $base  = $this->calculateTrendingScore($post);
            $boost = $this->globalSimilarityBoost($embedding, $prepared, $post->id);

            return [
                '_model'    => $post,
                'id'        => $post->id,
                'embedding' => $embedding,
                'score'     => $base + $boost,
            ];
        }, $prepared);

        // Free prepared from memory
        unset($prepared);

        // ── Step 3: Deduplicate by id ─────────────────────────────────────────
        $items = $this->deduplicateById($items);

        // ── Step 4: Semantic dedup ────────────────────────────────────────────
        $withEmb = array_values(array_filter($items, fn($i) => !empty($i['embedding'])));
        $without = array_values(array_filter($items, fn($i) =>  empty($i['embedding'])));
        $withEmb = $this->embedding->deduplicate($withEmb, 0.88);
        $items   = array_merge($withEmb, $without);
        unset($withEmb, $without);

        // ── Step 5: Sort before author cap ───────────────────────────────────
        usort($items, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        // ── Step 6: Author diversity cap ──────────────────────────────────────
        $authorCounts = [];
        $items = array_values(array_filter($items, function ($item) use (&$authorCounts) {
            $authorId = $item['_model']->user_id ?? 0;
            $authorCounts[$authorId] = ($authorCounts[$authorId] ?? 0) + 1;
            return $authorCounts[$authorId] <= self::MAX_POSTS_PER_AUTHOR;
        }));

        // ── Step 7: Topic detection ───────────────────────────────────────────
        $items = $this->topicDetector->detectBatch($items);

        // ── Step 8: Feed mixing ───────────────────────────────────────────────
        $items = $this->feedMixer->mix($items);

        // ── Step 9: Final mapping — free _model and embedding after use ───────
        $result = array_values(array_filter(array_map(function ($item) {
            $post = $item['_model'] ?? null;
            if (!$post) return null;

            $mapped = [
                'id'          => $post->id,
                'title'       => $post->title,
                'excerpt'     => Str::limit(strip_tags($post->content), 180),
                'cover_image' => $post->cover_image,
                'image_url'   => $post->image_url,

                'author' => [
                    'id'         => $post->user?->id,
                    'name'       => $post->user?->name,
                    'username'   => $post->user?->username,
                    'avatar_url' => $post->user?->avatar_url,
                ],

                'views'           => $post->views,
                'tags'            => $post->tags->pluck('name')->toArray(),
                'trending_score'  => round($item['score'] ?? 0, 2),
                'has_embedding'   => !empty($item['embedding']),
                'comments_count'  => $post->comments_count,
                'reactions_count' => $post->reactions_count,
                'created_at'      => $post->created_at?->toIso8601String(),
            ];

            // Explicitly free heavy objects
            unset($item['_model'], $item['embedding']);

            return $mapped;
        }, $items)));

        unset($items);

        return $result;
    }

    // ─── Scoring ──────────────────────────────────────────────────────────────

    private function calculateTrendingScore(Post $post): float
    {
        $views     = log10($post->views + 1) * 35;
        $comments  = sqrt($post->comments_count + 1) * 20;
        $reactions = sqrt($post->reactions_count + 1) * 25;
        $hours     = max(1, now()->diffInHours($post->created_at));
        $freshness = 120 / pow($hours + 2, 0.45);

        return round($views + $comments + $reactions + $freshness, 2);
    }

    /**
     * Compute a similarity boost from the top-5 most similar posts in the feed.
     *
     * Capped at 0.125 so embedding similarity stays a gentle enhancement
     * rather than overriding the popularity/recency signals. Previously this
     * multiplied by 5 which caused high-similarity posts to jump ~4 points,
     * dominating the entire score.
     */
    private function globalSimilarityBoost(array $postVector, array $items, int $postId): float
    {
        if (empty($postVector)) return 0;

        $boost = 0;
        $count = 0;

        foreach ($items as $item) {
            if (($item['_model']->id ?? null) === $postId) continue;
            if ($count >= 5) break;

            $vec = $item['embedding'] ?? [];
            if (empty($vec)) continue;

            $boost = max($boost, $this->embedding->cosine($postVector, $vec));
            $count++;
        }

        // FIX: was $boost * 5 — multiplier of 5 caused similarity to dwarf all
        // other signals. Capped at 0.125 so it enhances without dominating.
        return min($boost * 0.125, 0.125);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function deduplicateById(array $items): array
    {
        $seen = [];

        return array_values(array_filter($items, function ($item) use (&$seen) {
            $id = $item['_model']->id ?? null;
            if (!$id || isset($seen[$id])) return false;
            $seen[$id] = true;
            return true;
        }));
    }

    // ─── Tags ─────────────────────────────────────────────────────────────────

    public function getTrendingTags(): array
    {
        return Tag::query()
            ->withCount(['posts' => fn($q) => $q->where('status', 'published')])
            ->orderByDesc('posts_count')
            ->take(10)
            ->get(['id', 'name'])
            ->map(fn($tag) => [
                'id'    => $tag->id,
                'name'  => $tag->name,
                'count' => $tag->posts_count,
            ])
            ->toArray();
    }
}
