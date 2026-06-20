<?php

namespace App\Filament\Widgets;

use AlizHarb\ActivityLog\Widgets\ActivityChartWidget as BaseActivityChartWidget;

class ActivityChartDashboardWidget extends BaseActivityChartWidget
{
    protected static ?int $sort = -10;

    protected int | string | array $columnSpan = 'full';
}
