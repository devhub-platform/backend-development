<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\User;

class AITopicsUserController extends Controller
{
    public function index()
    {
        $userId = request()->query('user_id');

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'user_id parameter is required',
                'data' => null
            ], 400);
        }

        return $this->getUserTopics($userId);
    }

    public function showByUserId($userId)
    {
        return $this->getUserTopics($userId);
    }

    private function getUserTopics($userId)
    {
        $user = User::with('topics')->find($userId);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        $interests = $user->topics->pluck('name')->toArray();

        return response()->json([
            'user_id' => $user->id,
            'interests' => $interests
        ], 200);
    }
}
