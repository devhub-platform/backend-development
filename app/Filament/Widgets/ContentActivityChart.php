<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\ChartWidget;

class ContentActivityChart extends ChartWidget
{
    protected ?string $heading = 'Content activity (last 14 days)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $trend = app(AdminAnalyticsService::class)->contentTrend(14);

        return [
            'datasets' => [
                [
                    'label' => 'Posts',
                    'data' => $trend['posts'],
                ],
                [
                    'label' => 'Questions',
                    'data' => $trend['questions'],
                ],
                [
                    'label' => 'Views',
                    'data' => $trend['views'],
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
