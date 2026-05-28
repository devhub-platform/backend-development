<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    private const NOTIFICATION_COLUMNS = ['id', 'type', 'data', 'created_at', 'read_at'];
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
            ->orderBy('created_at', 'desc')
            ->get(self::NOTIFICATION_COLUMNS);
    }

    private function unreadByType(User $user, string $type)
    {
        return $this->unreadByTypes($user, [$type]);
    }

    private function validPreferenceTypes(): array
    {
        return array_keys(User::getDefaultNotificationPreferences());
    }

    public function showNewCommentNotify(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_NEW_COMMENT);
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_NEW_COMMENT)
                    ->count(),
            ],
        ]);
    }

    public function showNewReactNotify(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_REACT);
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
//                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_REACT)
                    ->count(),
            ],
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
        $notifications = $this->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->get(self::NOTIFICATION_COLUMNS);

        // Transform notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
//                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()->count(),
            ],
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

        // Transform and reshape the notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'follower_id' => $notification->data['follower_id'],
                'follower_name' => $notification->data['follower_name'],
                'follower_username' => $notification->data['follower_username'],
                'follower_avatar' => $notification->data['follower_avatar'],
                'total_followers' => $notification->data['total_followers'],
                'created_at' => $notification->created_at,
                'read_at' => $notification->read_at,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_FOLLOW)
                    ->count(),
            ],
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

        // Transform and reshape the notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'author_id' => $notification->data['author_id'],
                'author_name' => $notification->data['author_name'],
                'author_username' => $notification->data['author_username'],
                'author_avatar' => $notification->data['author_avatar'],
                'post_id' => $notification->data['post_id'],
                'post_title' => $notification->data['post_title'],
                'post_slug' => $notification->data['post_slug'],
                'message' => $notification->data['message'],
                'created_at' => $notification->created_at,
//                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
//                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
//                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_NEW_POST)
                    ->count(),
            ],
        ]);
    }

    public function showNewMentionNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_MENTION);

        // Transform notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
//                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
//                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
//                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_MENTION)
                    ->count(),
            ],
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
        $validTypes = $this->validPreferenceTypes();

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        $incomingTypes = array_keys($validated['preferences']);
        $invalidTypes = array_values(array_diff($incomingTypes, $validTypes));

        if ($invalidTypes !== []) {
            return response()->json([
                'message' => 'Invalid notification type',
                'invalid_types' => $invalidTypes,
                'valid_types' => $validTypes,
            ], 422);
        }

        $currentPreferences = $user->getNotificationPreferences();

        foreach ($validated['preferences'] as $type => $enabled) {
            $currentPreferences[$type] = (bool)$enabled;
        }

        $user->update(['notification_preferences' => $currentPreferences]);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'notification_preferences' => $user->fresh()->getNotificationPreferences(),
        ]);
    }

    public function toggleNotificationPreference(string $type): JsonResponse
    {
        $user = $this->user();
        $validTypes = $this->validPreferenceTypes();

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

        // Transform notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
//                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
//                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
//                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_QUESTION_CREATED)
                    ->count(),
            ],
        ]);
    }

    public function getAnswersNotifications(): JsonResponse
    {
        $notifications = $this->unreadByType($this->user(), self::TYPE_NEW_ANSWER);

        // Transform notification data for better layout
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
//                'created_at_formatted' => $notification->created_at ? $notification->created_at->format('M d, Y \a\t h:i A') : null,
//                'created_at_relative' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
                'read_at' => $notification->read_at,
//                'read_at_formatted' => $notification->read_at ? $notification->read_at->format('M d, Y \a\t h:i A') : null,
            ];
        })->values();

        return response()->json([
            'data' => $transformedNotifications,
            'meta' => [
                'total' => $transformedNotifications->count(),
                'unread_count' => $this->user()->unreadNotifications()
                    ->where('type', self::TYPE_NEW_ANSWER)
                    ->count(),
            ],
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
