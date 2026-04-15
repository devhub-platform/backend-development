<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Admin Analytics';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $analytics = app(AdminAnalyticsService::class)->overview();

        return [
            Stat::make('Users', number_format($analytics['total_users']))
                ->description(number_format($analytics['active_today']) . ' active today')
                ->color('primary'),
            Stat::make('Published posts', number_format($analytics['published_posts']))
                ->description(number_format($analytics['total_posts']) . ' total posts')
                ->color('success'),
            Stat::make('Resolved questions', number_format($analytics['resolved_questions']))
                ->description(number_format($analytics['total_questions']) . ' total questions')
                ->color('warning'),
            Stat::make('Views', number_format($analytics['post_views'] + $analytics['question_views']))
                ->description('Posts: ' . number_format($analytics['post_views']) . ' | Q&A: ' . number_format($analytics['question_views']))
                ->color('info'),
        ];
    }
}
