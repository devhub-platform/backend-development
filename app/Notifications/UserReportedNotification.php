<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReportedNotification extends Notification
{
    public function __construct(public Report $report)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New User Report Submitted')
            ->greeting('Hello Admin,')
            ->line('A new user report has been submitted.')
            ->line('Reported by: ' . $this->report->reporter->name)
            ->line('Reported user: ' . $this->report->reportedUser->name)
            ->line('Reason:')
            ->line($this->report->message ?? 'No message provided.')
            ->line('Reported at: ' . $this->report->created_at->toDayDateTimeString());
    }

    public function toArray($notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'reported_by' => $this->report->reporter->name,
            'reported_user' => $this->report->reportedUser->name,
            'reason' => $this->report->message,
            'reported_at' => $this->report->created_at,
        ];
    }
}

