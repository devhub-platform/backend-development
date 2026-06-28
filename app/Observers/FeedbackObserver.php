<?php

namespace App\Observers;

use App\Events\FeedbackSubmitted;
use App\Models\Feedback;
use App\Models\User;
use Filament\Notifications\Notification;

class FeedbackObserver
{
//    public function created(Feedback $feedback): void
//    {
//        // Broadcast the feedback submission event in real-time
//        FeedbackSubmitted::dispatch($feedback);
//
//        // Get all admin users and send notifications
//        $admins = User::where('role', 'admin')->get();
//
//        foreach ($admins as $admin) {
//            Notification::make()
//                ->title('New Feedback Received')
//                ->body('New feedback from ' . ($feedback->user?->name ?? 'Anonymous') . ': ' . substr($feedback->message ?? $feedback->content ?? '', 0, 50) . '...')
//                ->icon('heroicon-o-chat-bubble-left')
//                ->actions([
//                    \Filament\Notifications\Actions\Action::make('view')
//                        ->button()
//                        ->url('/admin/feedbacks/' . $feedback->id)
//                        ->close(),
//                ])
//                ->broadcast($admin);
//        }
//    }
}
