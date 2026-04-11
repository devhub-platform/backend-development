<?php

namespace App\Http\Controllers\V1\Chats;

use App\Events\UserOffline;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOfflineController extends Controller
{
    public function __invoke(Request $request, ?User $user = null): JsonResponse
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = $user ?? $authUser;

        $user->update([
            'status' => 'offline',
            'last_seen_at' => now(),
        ]);

        broadcast(new UserOffline($user))->toOthers();

        return response()->json([
            'message' => 'User is now offline',
            'data' => [
                'id' => $user->id,
                'status' => 'offline',
                'is_online' => false,
                'last_seen_at' => $user->lastSeenAtIso(),
            ],
        ]);
    }
}
