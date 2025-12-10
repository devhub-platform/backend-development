<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\UserStatusesRequest;
use App\Http\Resources\UserStatusesResource;
use App\Models\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserStatusesController
{
    public function store(UserStatusesRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $status = UserStatus::create($validated);

        return response()->json([
            'message' => 'Status created successfully',
            'status' => new UserStatusesResource($status),
        ], 201);
    }

    public function delete()
    {
        $user = Auth::user();
        $status = UserStatus::where('user_id', $user->id)->first();

        if (!$status) {
            return response()->json([
                'message' => 'No status found to delete',
            ], 404);
        }

        $status->delete();
        Log::notice('Status deleted successfully from user ID: ' . $user->id);
        return response()->json([
            'message' => 'Status deleted successfully',
        ], 200);
    }

    public function getStatuses()
    {
        $statuses = UserStatus::with('user')->get();

        return response()->json([
            'status' => UserStatusesResource::collection($statuses),
        ], 200);
    }
}
