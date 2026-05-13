<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class FeedbackSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Feedback $feedback)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Feedback: ' . $this->feedback->title,
            'body' => $this->feedback->user?->name . ' submitted feedback: ' . substr($this->feedback->message, 0, 100) . '...',
            'feedback_id' => $this->feedback->id,
            'type' => 'feedback',
            'action_url' => '/admin/feedbacks/' . $this->feedback->id . '/edit',
        ];
    }
}
