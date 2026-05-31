<?php

namespace App\Console\Commands;

use App\Services\Trending\TrendingService;
use App\Services\Trending\TechTrendService;
use App\Services\AI\TrendEnrichmentService;
use Illuminate\Console\Command;

class WarmTrendingCache extends Command
{
    protected $signature   = 'trending:warm {--with-ai : Also pre-warm AI enrichment for top items}';
    protected $description = 'Pre-warm trending caches so the first real request is instant';

    public function handle(
        TrendingService       $trendingService,
        TechTrendService      $techTrendService,
        TrendEnrichmentService $enrichment,
    ): void {
        // 1. Warm posts feed
        $this->info('Warming trending posts cache...');
        $trendingService->getTrendingPosts(tagId: null, perPage: 10, page: 1);
        $this->info('✓ Posts cache warmed');

        // 2. Warm tech trends feed (no AI)
        $this->info('Warming tech trends cache...');
        $trends = $techTrendService->getSharedTrends();
        $this->info('✓ Tech trends cache warmed (' . count($trends) . ' items)');

        // 3. Optionally pre-warm AI for top 5 items
        if ($this->option('with-ai')) {
            $this->info('Pre-warming AI enrichment for top 5 items...');
            $top5 = array_slice($trends, 0, 5);
            $enrichment->enrichBatch($top5);
            $this->info('✓ AI enrichment warmed for top 5 items');
        }
    }
}
