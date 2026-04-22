<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Services\AI\EmbeddingService;
use App\Services\AI\FeedMixer;
use App\Services\AI\TopicDetector;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrendingService
{
    private const CACHE_TTL = 5;

    public function __construct(
        private EmbeddingService $embedding,
        private TopicDetector    $topicDetector,
        private FeedMixer        $feedMixer,
    ) {}

    public function getTrendingPosts(?int $tagId = null, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->get('page', 1);

        $cacheKey = 'trending:v14:' . md5(json_encode([
                $tagId,
                request()->query(),
            ]));

        $allItems = Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL),
            fn() => $this->buildPipeline($tagId)
        );

        $slice = array_slice($allItems, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $slice,
            count($allItems),
            $perPage,
            $page,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function buildPipeline(?int $tagId): array
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->with(['user', 'tags'])
            ->when($tagId, fn($q) =>
            $q->whereHas('tags', fn($q) => $q->where('tags.id', $tagId))
            )
            ->limit(15)
            ->get();

        if ($posts->isEmpty()) return [];

        /*
        |----------------------------------------------------------------------
        | 1. Prepare embeddings
        |
        |    Read only what is already stored in the DB — no API calls here.
        |    Posts without an embedding are collected and dispatched via defer()
        |    so they are embedded after the response has been sent.
        |----------------------------------------------------------------------
        */
        $postsNeedingEmbedding = [];

        $prepared = $posts->map(function (Post $post) use (&$postsNeedingEmbedding) {

            $embedding = $this->embedding->getCachedEmbedding($post);

            if (empty($embedding)) {
                $postsNeedingEmbedding[] = $post->id;
            }

            return [
                '_model'    => $post,
                'embedding' => $embedding,
            ];

        })->values()->toArray();

        /*
        |----------------------------------------------------------------------
        | Defer embedding for posts that are missing a vector.
        |
        | NOTE: If you do NOT see "[defer] fired" in your logs after this runs,
        | defer() is not supported on your server/hosting. In that case you will
        | need a scheduled command (e.g. posts:backfill-embeddings) instead.
        |----------------------------------------------------------------------
        */
//        defer(function () use ($postsNeedingEmbedding) {
//
//            Log::info('[TrendingService][defer] fired', [
//                'posts_needing_embedding' => $postsNeedingEmbedding,
//            ]);
//
//            if (empty($postsNeedingEmbedding)) return;
//
//            $posts = Post::whereIn('id', $postsNeedingEmbedding)->get();
//
//            foreach ($posts as $post) {
//                $vector = app(EmbeddingService::class)->embedPost($post);
//
//                Log::info('[TrendingService][defer] embedding result', [
//                    'post_id'    => $post->id,
//                    'has_vector' => !empty($vector),
//                ]);
//            }
//        });

        /*
        |----------------------------------------------------------------------
        | 2. Build items + scoring
        |
        |    trending_score is calculated here and persisted to the DB via
        |    updateQuietly() so it is available for direct DB queries/sorting.
        |----------------------------------------------------------------------
        */
        $items = array_map(function ($item) use ($prepared) {

            /** @var Post $post */
            $post      = $item['_model'];
            $embedding = $item['embedding'];

            $boost = $this->globalSimilarityBoost($embedding, $prepared, $post->id);
            $score = $this->calculateTrendingScore($post) + $boost;


            Log::info('[TrendingService] scored', [
                'post_id' => $post->id,
                'score'   => $score,
                'boost'   => $boost,
            ]);

            return [
                '_model'    => $post,

                'id'        => $post->id,
                'title'     => $post->title,
                'content'   => Str::limit($post->content, 600),

                'tags'      => $post->tags->pluck('name')->toArray(),
                'views'     => $post->views,

                'embedding' => $embedding,
                'score'     => $score,
            ];

        }, $prepared);

        /*
        |----------------------------------------------------------------------
        | 3. Deduplication
        |----------------------------------------------------------------------
        */
        $items = $this->deduplicateById($items);

        $withEmb = array_values(array_filter($items, fn($i) => !empty($i['embedding'])));
        $without = array_values(array_filter($items, fn($i) =>  empty($i['embedding'])));

        $withEmb = $this->embedding->deduplicate($withEmb, 0.88);

        $items = array_merge($withEmb, $without);

        /*
        |----------------------------------------------------------------------
        | 4. Topic detection
        |----------------------------------------------------------------------
        */
        $items = $this->topicDetector->detectBatch($items);

        /*
        |----------------------------------------------------------------------
        | 5. Feed mixing
        |----------------------------------------------------------------------
        */
        $items = $this->feedMixer->mix($items);

        /*
        |----------------------------------------------------------------------
        | 6. Final output — strip internal fields before returning
        |----------------------------------------------------------------------
        */
        return array_values(array_map(function ($item) {

            $post = $item['_model'] ?? null;
            if (!$post) return null;

            return [
                'id'             => $post->id,
                'title'          => $post->title,
                'content'        => $post->content,

                'views'          => $post->views,
                'tags'           => $post->tags->pluck('name')->toArray(),
                'trending_score' => round($item['score'] ?? 0, 2),
                'has_embedding'  => !is_null($post->embedded_at),
            ];

        }, array_filter($items)));
    }

    private function calculateTrendingScore(Post $post): float
    {
        $views   = log($post->views + 1) * 10;
        $days    = max(1, now()->diffInDays($post->created_at));
        $recency = 100 / $days;

        return ($views * 0.6) + ($recency * 0.4);
    }

    private function deduplicateById(array $items): array
    {
        $seen = [];

        return array_values(array_filter($items, function ($item) use (&$seen) {
            if (isset($seen[$item['id']])) return false;
            $seen[$item['id']] = true;
            return true;
        }));
    }

    private function globalSimilarityBoost(array $postVector, array $items, int $postId): float
    {
        // No vector means no similarity boost — skip the loop entirely
        if (empty($postVector)) return 0;

        $boost = 0;

        foreach ($items as $item) {

            if ($item['_model']->id === $postId) continue;

            // Only compare against popular posts to keep the boost meaningful
            if (($item['_model']->views ?? 0) < 2000) continue;

            $vec = $item['embedding'] ?? [];

            if (empty($vec)) continue;

            $boost = max(
                $boost,
                $this->embedding->cosine($postVector, $vec)
            );
        }

        return $boost * 5;
    }

    // This method retrieves the trending tags based on the number of published posts associated with each tag.
    public function getTrendingTags(): array
    {
        return Post::query()
            ->where('status', 'published')
            ->with('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->groupBy('id')
            ->map(function ($tagGroup) {
                return [
                    'id'    => $tagGroup->first()->id,
                    'name'  => $tagGroup->first()->name,
                    'count' => $tagGroup->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();
    }
}
