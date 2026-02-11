<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\ReportResource;
use App\Mail\SupportReportMail;
use App\Models\Report;
use App\Models\User;
use App\Notifications\UserReportedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ReportController
{
    public function block(User $target)
    {
        $user = auth()->user();
        abort_if($user->id === $target->id, 400, 'Cannot block yourself');


        if (!$target) {
            return response()->json([
                'message' => 'Target user not found',
            ], 404);
        }

        if ($user->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            return response()->json([
                'message' => "User $target->name is already blocked",
            ], 400);
        }

        $user->blockedUsers()->attach($target->id);

        return response()->json([
            'message' => "User $target->name blocked successfully",
        ]);
    }

    public function report(Request $request, User $target)
    {
        $user = auth()->user();
        abort_if($user->id === $target->id, 400, 'Cannot report yourself');

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $target->id,
            'message' => $validated['message'],
            'reason' => $validated['reason'],
        ]);

        if (!$user->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            $user->blockedUsers()->attach($target->id);
        }

        $adminMails = 'youssef.ahmed.fci@gmail.com';
        Notification::route('mail', $adminMails)
            ->notify(new UserReportedNotification($report));

        Mail::to(Auth::user()->email)
            ->send(new SupportReportMail($user, $report));

        return response()->json([
            'message' => "User $target->name reported and blocked successfully",
            'data' => new ReportResource($report->load(['reporter', 'reportedUser'])),
            'admin_notification_sent_to' => $adminMails,
        ]);
    }

    public function unblock(User $target)
    {
        $user = auth()->user();

        if (!$user->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            return response()->json(['message' => 'User is not blocked'], 400);
        }

        $user->blockedUsers()->detach($target->id);

        return response()->json(['message' => "User {$target->name} unblocked"]);
    }

    public function blockList()
    {
        $blockedUsers = auth()->user()->blockedUsers()->get();

        if ($blockedUsers->isEmpty()) {
            return response()->json([
                'message' => 'No blocked users found',
                'data' => [],
            ]);
        }

        return response()->json([
            'data' => $blockedUsers->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
            ]),
        ]);
    }

    public function reason()
    {
        return response()->json([
            'reasons' => [
                'Spam or misleading',
                'Hate speech or graphic violence',
                'Harassment or bullying',
                'Inappropriate content',
                'Fake profile',
                'Other',
            ],
        ]);
    }
}
