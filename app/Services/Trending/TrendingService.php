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
        private TopicDetector $topicDetector,
        private FeedMixer $feedMixer,
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
                'path' => request()->url(),
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
        |----------------------------------------------------------------------
        */
        $prepared = $posts->map(function (Post $post) {

            $embedding = $this->embedding->embedPost($post);

            return [
                '_model'    => $post,
                'embedding' => $embedding ?: [],
            ];
        })->values()->toArray();

        /*
        |----------------------------------------------------------------------
        | 2. Build items + scoring (WITH similarity boost)
        |----------------------------------------------------------------------
        */
        $items = array_map(function ($item) use ($prepared) {

            /** @var Post $post */
            $post = $item['_model'];
            $embedding = $item['embedding'];

            $boost = $this->globalSimilarityBoost($embedding, $prepared, $post->id);

            // optional debug
            Log::info('similarity', [
                'post'  => $post->id,
                'boost' => $boost,
            ]);

            return [
                '_model' => $post,

                'id'      => $post->id,
                'title'   => $post->title,
                'content' => Str::limit($post->content, 600),

                'tags'    => $post->tags->pluck('name')->toArray(),
                'views'   => $post->views,

                'embedding' => $embedding,

                'score' =>
                    $this->calculateTrendingScore($post)
                    + $boost,
            ];
        }, $prepared);

        /*
        |----------------------------------------------------------------------
        | 3. Deduplication
        |----------------------------------------------------------------------
        */
        $items = $this->deduplicateById($items);

        $withEmb = array_values(array_filter($items, fn($i) => !empty($i['embedding'])));
        $without = array_values(array_filter($items, fn($i) => empty($i['embedding'])));

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
        | 6. Final output
        |----------------------------------------------------------------------
        */
        return array_values(array_map(function ($item) {

            $post = $item['_model'] ?? null;
            if (!$post) return null;

            return [
                'id'      => $post->id,
                'title'   => $post->title,
                'content' => $post->content,

                'views'   => $post->views,
                'tags'    => $post->tags->pluck('name')->toArray(),

                'trending_score' => $item['score'] ?? 0,
                'has_embedding'  => !empty($item['embedding']),
            ];

        }, array_filter($items)));
    }

    private function calculateTrendingScore(Post $post): float
    {
        $views = log($post->views + 1) * 10;
        $days  = max(1, now()->diffInDays($post->created_at));

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
        $boost = 0;

        foreach ($items as $item) {


            if ($item['_model']->id === $postId) continue;

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
}
