<?php

namespace App\Observers;

use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackSubmittedNotification;

class FeedbackObserver
{
    public function created(Feedback $feedback): void
    {
        // Get all admin users
        $admins = User::where('role', 'admin')->get();

        // Send notification to each admin
        foreach ($admins as $admin) {
            $admin->notify(new FeedbackSubmittedNotification($feedback));
            $admin->notifyBell(); // broadcasts the NotificationSent event via Reverb
        }
    }
}
