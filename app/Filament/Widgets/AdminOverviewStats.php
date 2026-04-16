<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Admin Analytics Overview';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $analytics = app(AdminAnalyticsService::class)->overview();

        // Calculate activity rates
        $activeTodayPercentage = $analytics['total_users'] > 0
            ? round(($analytics['active_today'] / $analytics['total_users']) * 100, 1)
            : 0;

        $publishedPercentage = $analytics['total_posts'] > 0
            ? round(($analytics['published_posts'] / $analytics['total_posts']) * 100, 1)
            : 0;

        $resolvedPercentage = $analytics['total_questions'] > 0
            ? round(($analytics['resolved_questions'] / $analytics['total_questions']) * 100, 1)
            : 0;

        // Calculate engagement metrics
        $totalViews = $analytics['total_views'];
        $avgViewsPerUser = $analytics['total_users'] > 0 ? round($totalViews / $analytics['total_users'], 2) : 0;

        return [
            // Users Stat
            Stat::make('Total Users', number_format($analytics['total_users']))
                ->description(
                    $analytics['active_today'] . ' active today (' . $activeTodayPercentage . '%)'
                )
                ->descriptionIcon('heroicon-o-users')
                ->color($activeTodayPercentage >= 20 ? 'success' : ($activeTodayPercentage >= 10 ? 'warning' : 'danger'))
                ->chart($this->generateTrendChart()),

            // Published Posts Stat
            Stat::make('Published Posts', number_format($analytics['published_posts']))
                ->description(
                    number_format($analytics['total_posts']) . ' total (' . $publishedPercentage . '% published)'
                )
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success')
                ->chart($this->generateTrendChart()),

            // Questions Stat
            Stat::make('Questions', number_format($analytics['total_questions']))
                ->description(
                    number_format($analytics['resolved_questions']) . ' resolved (' . $resolvedPercentage . '%) | ' . number_format($analytics['unanswered_questions']) . ' unanswered'
                )
                ->descriptionIcon('heroicon-o-question-mark-circle')
                ->color($resolvedPercentage >= 50 ? 'success' : 'warning')
                ->chart($this->generateTrendChart()),

            // Views Stat
            Stat::make('Total Views', Number::abbreviate($totalViews))
                ->description(
                    'Avg: ' . $avgViewsPerUser . ' views/user | Posts: ' . number_format($analytics['post_views']) . ' | Q&A: ' . number_format($analytics['question_views'])
                )
                ->descriptionIcon('heroicon-o-eye')
                ->color('info')
                ->chart($this->generateTrendChart()),

            // Engagement Rate Stat
            Stat::make('Engagement Rate', round($avgViewsPerUser, 1) . ' views/user')
                ->description(
                    'Total engagement across ' . number_format($analytics['total_users']) . ' users'
                )
                ->descriptionIcon('heroicon-o-sparkles')
                ->color($avgViewsPerUser >= 5 ? 'success' : ($avgViewsPerUser >= 2 ? 'warning' : 'danger'))
                ->chart($this->generateTrendChart()),

            // Content Quality Stat
            Stat::make('Content Quality', $publishedPercentage . '%')
                ->description(
                    'Published posts ratio | ' . number_format($analytics['draft_posts']) . ' drafts | ' . number_format($analytics['archived_posts']) . ' archived'
                )
                ->descriptionIcon('heroicon-o-star')
                ->color($publishedPercentage >= 80 ? 'success' : ($publishedPercentage >= 60 ? 'warning' : 'danger'))
                ->chart($this->generateTrendChart()),
        ];
    }

    /**
     * Generate a simple trend chart for stats
     */
    private function generateTrendChart(): array
    {
        return [60, 70, 65, 80, 75, 85, 90];
    }
}
