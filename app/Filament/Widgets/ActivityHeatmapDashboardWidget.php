<?php

namespace App\Filament\Widgets;

use AlizHarb\ActivityLog\Widgets\ActivityHeatmapWidget as BaseActivityHeatmapWidget;

class ActivityHeatmapDashboardWidget extends BaseActivityHeatmapWidget
{
    protected static ?int $sort = 100;

    protected int | string | array $columnSpan = 'full';
}
