<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActivityChartDashboardWidget;
use App\Filament\Widgets\ActivityHeatmapDashboardWidget;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\ContentActivityChart;
use App\Filament\Widgets\TopPostsTable;
use App\Filament\Widgets\BroadcastNotificationWidget;
use App\Filament\Widgets\AIAnalyticsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';


    public function getWidgets(): array
    {
        return [
            ActivityChartDashboardWidget::class,
            AIAnalyticsWidget::class,
            AdminOverviewStats::class,
            ContentActivityChart::class,
            BroadcastNotificationWidget::class,
            TopPostsTable::class,
            ActivityHeatmapDashboardWidget::class,
        ];
    }

    public function getColumns(): array|int
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
}