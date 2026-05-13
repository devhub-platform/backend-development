<?php

namespace App\Observers;

use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportSubmittedNotification;

class ReportObserver
{
    public function created(Report $report): void
    {
        // Get all admin users
        $admins = User::where('role', 'admin')->get();

        // Send notification to each admin
        foreach ($admins as $admin) {
            $admin->notify(new ReportSubmittedNotification($report));
            $admin->notifyBell(); // broadcasts the NotificationSent event via Reverb
        }
    }
}
