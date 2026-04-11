<?php

namespace App\Http\Controllers\V1\Chats;

use App\Events\UserOnline;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOnlineController extends Controller
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

        $user->status = 'online';
        $user->last_seen_at = now();
        $user->save();

        broadcast(new UserOnline($user))->toOthers();

        return response()->json([
            'message' => 'User is now online',
            'data' => [
                'id' => $user->id,
                'status' => 'online',
                'is_online' => true,
                'last_seen_at' => $user->lastSeenAtIso(),
            ],
        ]);
    }
}
