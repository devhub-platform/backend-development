<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Musonza\Chat\Models\Conversation;

Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('mc-chat-conversation.{conversationId}', function ($user, $conversationId) {
    Log::debug("Authorizing user {$user->id} for conversation {$conversationId}");
    $conversation = Conversation::find($conversationId);
    if (!$conversation) {
        Log::debug("Conversation {$conversationId} not found");
        return false;
    }

    $isParticipant = $user->can('view', $conversation);
    Log::debug("User {$user->id} authorization for conversation {$conversationId}: " . ($isParticipant ? 'true' : 'false'));
    return $isParticipant;
});

Broadcast::channel('mc-chat-presence.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation || !$user->can('view', $conversation)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->username ?? $user->name,
        'avatar' => $user->avatar_url,
        'status' => $user->isOnline() ? 'online' : 'offline',
        'is_online' => $user->isOnline(),
        'last_seen_at' => $user->lastSeenAtIso(),
    ];
});

Broadcast::channel('users', function ($user) {
    return [
        'id' => $user->id,
        'username' => $user->username,
        'status' => $user->isOnline() ? 'online' : 'offline',
        'is_online' => $user->isOnline(),
    ];
});
