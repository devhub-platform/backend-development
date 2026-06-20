<?php

namespace App\Filament\Widgets;

use AlizHarb\ActivityLog\Widgets\ActivityHeatmapWidget as BaseActivityHeatmapWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ActivityHeatmapDashboardWidget extends BaseActivityHeatmapWidget
{
    protected static ?int $sort = 100;

    protected int | string | array $columnSpan = 'full';
    public function getData(): array
    {
        try {
            $heatmap = parent::getData();

            // Ensure data is a collection keyed by ISO date strings so the view can lookup reliably.
            $data = collect($heatmap['data'] ?? [])
                ->mapWithKeys(function ($value, $key) {
                    try {
                        $date = Carbon::parse($key)->toDateString();
                    } catch (\Throwable $e) {
                        return [];
                    }

                    return [$date => (int) $value];
                });

            return [
                'data' => $data,
                'max' => $data->max() ?: 1,
            ];
        } catch (\Throwable $e) {
            Log::error('ActivityHeatmapDashboardWidget error: ' . $e->getMessage());

            return [
                'data' => Collection::make(),
                'max' => 1,
            ];
        }
    }
}
