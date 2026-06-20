<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Report Submitted',
            'body' => $this->report->reporter?->name . ' reported ' . ($this->report->reportedUser?->name ?? 'a post') . ' for ' . ($this->report::REASONS[$this->report->reason] ?? $this->report->reason),
            'report_id' => $this->report->id,
            'type' => 'report',
            'action_url' => '/admin/reports/' . $this->report->id . '/edit',
        ];
    }
}
