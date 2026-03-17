<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    private const NOTIFICATION_COLUMNS = ['type', 'data', 'created_at'];
    private const TYPE_NEW_COMMENT = 'App\\Notifications\\NewCommentNotification';
    private const TYPE_REACT = 'App\\Notifications\\ReactNotification';
    private const TYPE_FOLLOW = 'App\\Notifications\\FollowNotification';
    private const TYPE_NEW_POST = 'App\\Notifications\\NewPostNotification';
    private const TYPE_MENTION = 'App\\Notifications\\MentionInCommentNotification';
    private const TYPE_QUESTION_CREATED = 'App\\Notifications\\QuestionCreatedNotification';
    private const TYPE_NEW_ANSWER = 'App\\Notifications\\NewAnswerNotification';

    private function user(): User
    {
        return auth()->user();
    }

    private function unreadByTypes(User $user, array $types)
    {
        return $user->unreadNotifications()
            ->whereIn('type', $types)
            ->get(self::NOTIFICATION_COLUMNS);
    }

    private function unreadByType(User $user, string $type)
    {
        return $this->unreadByTypes($user, [$type]);
    }

    public function showNewCommentNotify(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_NEW_COMMENT);

        return response()->json([
            'new_comment_notifications' => $notifications,
        ]);
    }

    public function showNewReactNotify(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_REACT);

        return response()->json([
            'new_react_notifications' => $notifications,
        ]);
    }

    public function makeAllRead(): JsonResponse
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function makeAsRead(string $slug): JsonResponse
    {
        $notification = $this->user()
            ->unreadNotifications()
            ->whereKey($slug)
            ->first();

        if ($notification) {
            $notification->markAsRead();

            return response()->json([
                'message' => 'Notification marked as read',
            ]);
        }

        return response()->json([
            'message' => 'Notification not found',
        ], 404);
    }

    public function showAllNotifications(): JsonResponse
    {
        $notifications = $this->user()->notifications;

        return response()->json([
            'all_notifications' => $notifications,
        ]);
    }

    public function clearAllNotifications(): JsonResponse
    {
        $this->user()->notifications()->delete();

        return response()->json([
            'message' => 'All notifications deleted',
        ]);
    }

    public function showNewFollowersNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_FOLLOW);

        return response()->json([
            'new_follower_notifications' => $notifications,
        ]);
    }

    public function clearAllNotificationFromFollowers(): JsonResponse
    {
        $this->user()
            ->unreadNotifications()
            ->where('type', self::TYPE_FOLLOW)
            ->delete();

        return response()->json([
            'message' => 'All follower notifications deleted',
        ]);
    }

    public function newPostCreateFromFollower(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_NEW_POST);

        return response()->json([
            'new_post_from_follower_notifications' => $notifications,
        ]);
    }

    public function showNewMentionNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_MENTION);

        return response()->json([
            'new_mention_notifications' => $notifications,
        ]);
    }

    public function getNotificationPreferences(): JsonResponse
    {
        return response()->json([
            'notification_preferences' => $this->user()->getNotificationPreferences(),
        ]);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $user = $this->user();

        $validTypes = array_keys(User::getDefaultNotificationPreferences());

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        $currentPreferences = $user->getNotificationPreferences();

        foreach ($validated['preferences'] as $type => $enabled) {
            if (in_array($type, $validTypes, true)) {
                $currentPreferences[$type] = (bool)$enabled;
            }
        }

        $user->update(['notification_preferences' => $currentPreferences]);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'notification_preferences' => $user->fresh()->getNotificationPreferences(),
        ]);
    }

    public function toggleNotificationPreference(Request $request, string $type): JsonResponse
    {
        $user = $this->user();

        $validTypes = array_keys(User::getDefaultNotificationPreferences());

        if (!in_array($type, $validTypes, true)) {
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

    public function getQuestionsNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_QUESTION_CREATED);

        return response()->json([
            'questions_notifications' => $notifications,
        ]);
    }

    public function getAnswersNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_NEW_ANSWER);

        return response()->json([
            'answers_notifications' => $notifications,
        ]);
    }

    public function storePlayerId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|string|uuid',
        ]);

        $this->user()->update([
            'onesignal_player_id' => $validated['player_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OneSignal player ID saved successfully',
        ]);
    }

    public function removePlayerId(): JsonResponse
    {
        $this->user()->update([
            'onesignal_player_id' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OneSignal player ID removed successfully',
        ]);
    }
}
