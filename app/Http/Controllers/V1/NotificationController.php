<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use Illuminate\Http\Request;

class NotificationController
{
    public function showNewCommentNotify()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\NewCommentNotification')->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'new_comment_notifications' => $notifications,
        ]);
    }

    public function showNewReactNotify()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\ReactNotification')->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'new_react_notifications' => $notifications,
        ]);
    }

    public function makeAllRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function makeAsRead(string $slug)
    {
        $user = auth()->user();
        $notification = $user->unreadNotifications->find($slug);
        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'message' => 'Notification marked as read',
            ]);
        } else {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }
    }

    public function showAllNotifications()
    {
        $user = auth()->user();
        $notifications = $user->notifications;
        return response()->json([
            'all_notifications' => $notifications,
        ]);
    }

    public function clearAllNotifications()
    {
        $user = auth()->user();
        $user->notifications()->delete();
        return response()->json([
            'message' => 'All notifications deleted',
        ]);
    }

    public function showNewFollowersNotifications()
    {
        $user = auth()->user(); // youssef
        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\FollowNotification')->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'new_follower_notifications' => $notifications,
        ]);
    }

    public function clearAllNotificationFromFollowers()
    {
        $user = auth()->user();
        $user->unreadNotifications()
            ->where('type', 'App\Notifications\FollowNotification')
            ->delete();
        return response()->json([
            'message' => 'All follower notifications deleted',
        ]);
    }

    public function newPostCreateFromFollower()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\NewPostNotification')->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'new_post_from_follower_notifications' => $notifications,
        ]);
    }

    # notification for mention in comment

    public function showNewMentionNotifications()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\MentionInCommentNotification')->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'new_mention_notifications' => $notifications,
        ]);
    }

    public function getNotificationPreferences()
    {
        $user = auth()->user();
        return response()->json([
            'notification_preferences' => $user->getNotificationPreferences(),
        ]);
    }

    public function updateNotificationPreferences(Request $request)
    {
        $user = auth()->user();

        $validTypes = array_keys(User::getDefaultNotificationPreferences());

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        $currentPreferences = $user->getNotificationPreferences();

        foreach ($validated['preferences'] as $type => $enabled) {
            if (in_array($type, $validTypes)) {
                $currentPreferences[$type] = (bool)$enabled;
            }
        }

        $user->update(['notification_preferences' => $currentPreferences]);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'notification_preferences' => $user->fresh()->getNotificationPreferences(),
        ]);
    }

    public function toggleNotificationPreference(Request $request, string $type)
    {
        $user = auth()->user();

        $validTypes = array_keys(User::getDefaultNotificationPreferences());

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'message' => 'Invalid notification type',
                'valid_types' => $validTypes,
            ], 400);
        }

        $currentPreferences = $user->getNotificationPreferences();
        $newValue = !($currentPreferences[$type] ?? true);

        $user->updateNotificationPreference($type, $newValue);

        return response()->json([
            'message' => "Notification '{$type}' " . ($newValue ? 'enabled' : 'disabled'),
            'type' => $type,
            'enabled' => $newValue,
        ]);
    }

    public function getQuestionsNotifications()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\QuestionCreatedNotification',
            ])->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'questions_notifications' => $notifications,
        ]);
    }

    public function getAnswersNotifications()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\NewAnswerNotification',
            ])->get([
                'type', 'data', 'created_at'
            ]);
        return response()->json([
            'answers_notifications' => $notifications,
        ]);
    }
}
