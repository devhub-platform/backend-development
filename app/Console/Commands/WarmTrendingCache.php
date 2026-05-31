<?php

namespace App\Console\Commands;

use App\Services\Trending\TrendingService;
use App\Services\Trending\TechTrendService;
use App\Services\AI\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmTrendingCache extends Command
{
    protected $signature   = 'trending:warm';
    protected $description = 'Pre-warm trending caches so the first real request is instant';

    public function handle(
        TrendingService  $trendingService,
        TechTrendService $techTrendService,
        EmbeddingService $embedding,
    ): void {
        ini_set('memory_limit', '512M');

        // 1. Warm posts feed
        $this->info('Warming trending posts cache...');
        $trendingService->getTrendingPosts(tagId: null, perPage: 10, page: 1);
        $this->info('✓ Posts cache warmed');

        // 2. Warm tech trends feed (fetch + score + mix, no embeddings yet)
        $this->info('Warming tech trends cache...');
        $trends = $techTrendService->getSharedTrends();
        $this->info('✓ Tech trends cache warmed (' . count($trends) . ' items)');

        // 3. Compute and cache embeddings for all tech trend items
        // This runs in the cron so requests never wait for embedding API calls
        $this->info('Computing embeddings for tech trends...');
        $computed = 0;

        foreach ($trends as $item) {
            $embKey = 'tech:emb:' . md5(($item['title'] ?? '') . ($item['description'] ?? ''));

            if (!Cache::has($embKey)) {
                $text   = ($item['title'] ?? '') . ' ' . ($item['description'] ?? '');
                $vector = $embedding->embed($text);

                if (!empty($vector)) {
                    Cache::put($embKey, $vector, now()->addDays(7));
                    $computed++;
                }
            }
        }

        $this->info("✓ Embeddings computed ({$computed} new, " . (count($trends) - $computed) . ' from cache)');
    }
}
