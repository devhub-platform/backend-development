<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Notifications\UserReportedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReportController
{
    public function block(User $target)
    {
        $user = auth()->user();
        abort_if($user->id === $target->id, 400, 'Cannot block yourself');

        if ($user->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            return response()->json([
                'message' => 'User is already blocked',
            ], 400);
        }

        $user->blockedUsers()->attach($target->id);

        return response()->json([
            'message' => 'User blocked successfully',
        ]);
    }

    public function report(Request $request, User $target)
    {
        $user = auth()->user();
        abort_if($user->id === $target->id, 400, 'Cannot report yourself');

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $target->id,
            'message' => $validated['message'],
        ]);

        $adminMails = 'youssef.ahmed.fci@gmail.com';
        Notification::route('mail', $adminMails)
            ->notify(new UserReportedNotification($report));

        return response()->json([
            'message' => 'User reported successfully',
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

        return response()->json(['message' => 'User unblocked']);
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
                'email' => $user->email,
            ]),
        ]);
    }
}
