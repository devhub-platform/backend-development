<?php

namespace App\Filament\Widgets;

use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AIAnalyticsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'AI Analytics';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getStats(): array
    {
        // Get AI chat statistics
        $totalSessions = AIChatSession::count();
        $totalMessages = AIChatMessage::count();
        $activeSessions = AIChatSession::where('active', true)->count();
        $closedSessions = AIChatSession::where('active', false)->count();

        // Get unique models used
        $modelsUsed = AIChatSession::distinct('model')->count('model');
        $modelsList = AIChatSession::distinct()->pluck('model')->toArray();

        // Calculate average messages per session
        $avgMessagesPerSession = $totalSessions > 0 ? round($totalMessages / $totalSessions, 1) : 0;

        // Get most used model
        $mostUsedModel = AIChatSession::select('model')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('model')
            ->orderByDesc('count')
            ->first();

        // Estimate tokens (rough estimate: ~4 chars per token)
        $estimatedTokens = DB::table('ai_chat_messages')
            ->sum(DB::raw('LENGTH(content)')) / 4;

        return [
            // Total Sessions
            Stat::make('AI Chat Sessions', number_format($totalSessions))
                ->description(
                    $activeSessions . ' active | ' . $closedSessions . ' closed'
                )
                ->descriptionIcon('heroicon-o-chat-bubble-left')
                ->color('success')
                ->chart([10, 15, 12, 20, 25, 22, 30]),

            // Total Messages
            Stat::make('Messages Exchanged', number_format($totalMessages))
                ->description(
                    'Avg: ' . $avgMessagesPerSession . ' per session'
                )
                ->descriptionIcon('heroicon-o-envelope')
                ->color('info')
                ->chart([5, 8, 10, 12, 15, 18, 20]),

            // Models Used
            Stat::make('AI Models', number_format($modelsUsed))
                ->description(
                    'Primary: ' . (basename($mostUsedModel?->model ?? 'N/A'))
                )
                ->descriptionIcon('heroicon-o-cog')
                ->color('warning')
                ->chart([1, 2, 2, 3, 3, 3, 3]),

            // Estimated Tokens
            Stat::make('Est. Tokens Used', number_format((int)$estimatedTokens))
                ->description(
                    'Total conversation tokens'
                )
                ->descriptionIcon('heroicon-o-bolt')
                ->color('primary')
                ->chart([100, 200, 300, 400, 500, 600, 700]),
        ];
    }
}

