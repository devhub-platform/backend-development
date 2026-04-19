<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Services\AI\EmbeddingService;
use App\Services\AI\FeedMixer;
use App\Services\AI\TopicDetector;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TrendingService
 *
 * Clean production pipeline:
 * - Score computation
 * - Deduplication
 * - Topic detection
 * - Diversity mixing
 */
class TrendingService
{
    private const CACHE_TTL = 30;
    private const DEDUP_THRESHOLD = 0.88;

    public function __construct(
        private EmbeddingService $embedding,
        private TopicDetector $topicDetector,
        private FeedMixer $feedMixer,
    ) {}

    /**
     * Public API
     */
    public function getTrendingPosts(?int $tagId = null, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->get('page', 1);

        $cacheKey = 'trending:v5:' . md5(json_encode([
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

    /**
     * PIPELINE CORE
     */
    private function buildPipeline(?int $tagId): array
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->with(['user', 'tags'])
            ->when($tagId, fn($q) =>
            $q->whereHas('tags', fn($q) => $q->where('tags.id', $tagId))
            )
            ->limit(80)
            ->get();

        if ($posts->isEmpty()) {
            return [];
        }

        /**
         * STEP 1: Normalize + scoring
         */
        $items = $posts->map(function ($post) {

            $score = $this->calculateTrendingScore($post);

            return [
                'id'      => $post->id,
                'title'   => $post->title,
                'content' => Str::limit($post->content, 800),

                'views' => $post->views,

                // core ranking
                'score' => $score,

                // for debugging/API
                'trending_score' => $score,

                // topic hint (fallback)
                'topic' => $post->tags->first()->name ?? 'general',

                '_model' => $post,
            ];
        })->toArray();

        /**
         * STEP 2: Deduplication
         */
        $deduped = $this->deduplicate($items);

        /**
         * STEP 3: Topic detection
         */
        $withTopics = $this->topicDetector->detectBatch($deduped);

        /**
         * STEP 4: Feed mixing
         */
        $mixed = $this->feedMixer->mix($withTopics);

        /**
         * STEP 5: FIX (IMPORTANT)
         * Inject computed fields into model safely
         */
        return array_map(function ($item) {
            $post = $item['_model'];

            $post->trending_score = $item['trending_score'] ?? 0;
            $post->score = $item['score'] ?? 0;
            $post->topic = $item['topic'] ?? 'general';

            return $post;
        }, $mixed);
    }

    /**
     * Trending score (balanced version)
     */
    private function calculateTrendingScore(Post $post): float
    {
        $viewsScore = log($post->views + 1) * 10;

        $daysOld = max(1, now()->diffInDays($post->created_at));
        $recency = 100 / $daysOld;

        return ($viewsScore * 0.6) + ($recency * 0.4);
    }

    /**
     * Deduplication engine
     */
    private function deduplicate(array $items): array
    {
        $vectors = [];
        $kept = [];

        foreach ($items as $i => $item) {
            $post = $item['_model'];

            $vectors[$i] = !empty($post->embedding)
                ? (is_array($post->embedding)
                    ? $post->embedding
                    : json_decode($post->embedding, true))
                : null;
        }

        foreach ($items as $i => $item) {

            if (empty($vectors[$i])) {
                $kept[] = $i;
                continue;
            }

            $duplicate = false;

            foreach ($kept as $k) {
                if (empty($vectors[$k])) continue;

                if ($this->embedding->cosine($vectors[$i], $vectors[$k]) >= self::DEDUP_THRESHOLD) {
                    $duplicate = true;
                    break;
                }
            }

            if (!$duplicate) {
                $kept[] = $i;
            }
        }

        return array_values(array_intersect_key($items, array_flip($kept)));
    }

    /**
     * Trending tags
     */
    public function getTrendingTags(): array
    {
        return Cache::remember('trending:tags:v2', now()->addMinutes(60), function () {
            return DB::table('post_tags')
                ->join('tags', 'tags.id', '=', 'post_tags.tag_id')
                ->select('tags.id', 'tags.name', DB::raw('COUNT(*) as post_count'))
                ->where('post_tags.created_at', '>=', now()->subDays(7))
                ->groupBy('tags.id', 'tags.name')
                ->orderByDesc('post_count')
                ->limit(20)
                ->get()
                ->toArray();
        });
    }
}
