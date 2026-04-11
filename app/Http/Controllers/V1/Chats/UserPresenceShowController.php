<?php

namespace App\Http\Controllers\V1\Chats;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserPresenceShowController extends Controller
{
    public function __invoke(User $user): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $user->id,
                'status' => $user->isOnline() ? 'online' : 'offline',
                'is_online' => $user->isOnline(),
                'last_seen_at' => $user->lastSeenAtIso(),
            ],
        ]);
    }
}

