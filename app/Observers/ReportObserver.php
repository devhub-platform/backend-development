<?php

namespace App\Observers;

use App\Events\ReportSubmitted;
use App\Models\Report;
use App\Models\User;
use Filament\Notifications\Notification;

class ReportObserver
{
    public function created(Report $report): void
    {
        // Broadcast the report submission event in real-time
        ReportSubmitted::dispatch($report);
        
        // Get all admin users and send notifications
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::make()
                ->title('New Report Submitted')
                ->body('A new report has been submitted by ' . ($report->reporter?->name ?? 'Anonymous'))
                ->icon('heroicon-o-exclamation-triangle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url('/admin/reports/' . $report->id)
                        ->close(),
                ])
                ->broadcast($admin);
        }
    }
}