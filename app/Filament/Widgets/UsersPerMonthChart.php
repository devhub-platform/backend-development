<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersPerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Users Per Month';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = [];
        $data = [];

        // Get user counts for each month of the current year
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::createFromDate(now()->year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $count = User::whereBetween('created_at', [$startDate, $endDate])->count();

            $months[] = $startDate->format('M');
            $data[] = $count;
        }

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data,
                    'borderColor' => '#f97316',
                    'backgroundColor' => '#f973164d',
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
