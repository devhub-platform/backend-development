<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\UserStatusesRequest;
use App\Http\Resources\UserStatusesResource;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserStatusesController
{
    /**
     * Create or update user status
     * POST /api/v1/user/statuses
     */
    public function store(UserStatusesRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();
            $validated['user_id'] = $user->id;

            // Delete expired status if exists
            $this->deleteExpiredStatus($user->id);

            // Create or update status (only one status per user)
            $status = UserStatus::updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );

            Log::info('Status created/updated for user ID: ' . $user->id, [
                'status_id' => $status->id,
                'data' => $validated,
            ]);

            return response()->json([
                'message' => 'Status created successfully',
                'data' => new UserStatusesResource($status),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all statuses for authenticated user
     * GET /api/v1/user/statuses
     */
    public function getStatuses()
    {
        try {
            $user = Auth::user();

            // Delete expired statuses
            $this->deleteExpiredStatus($user->id);

            $status = $user->status;

            if (!$status) {
                return response()->json([
                    'message' => 'No status found',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Status retrieved successfully',
                'data' => new UserStatusesResource($status),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get status for a specific user by username
     * GET /api/v1/users/{username}/status
     */
    public function getUserStatus($id)
    {
        try {
            $user = User::where('id', $id)->firstOrFail();

            // Delete expired status if exists
            $this->deleteExpiredStatus($user->id);

            $status = $user->status;

            if (!$status) {
                return response()->json([
                    'message' => 'No status found for this user',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'message' => 'Status retrieved successfully',
                'data' => new UserStatusesResource($status),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving user status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UserStatusesRequest $request)
    {
        try {
            $user = auth()->user();
            $validated = $request->validated();

            $status = UserStatus::where('user_id', $user->id)->first();

            if (!$status) {
                return response()->json([
                    'message' => 'No status found to update',
                ], 404);
            }

            $status->update($validated);

            Log::info('Status updated for user ID: ' . $user->id, [
                'status_id' => $status->id,
                'updated_fields' => $validated,
            ]);

            return response()->json([
                'message' => 'Status updated successfully',
                'data' => new UserStatusesResource($status),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete()
    {
        try {
            $user = Auth::user();
            $status = UserStatus::where('user_id', $user->id)->first();

            if (!$status) {
                return response()->json([
                    'message' => 'No status found to delete',
                ], 404);
            }

            $status->delete();

            Log::notice('Status deleted successfully from user ID: ' . $user->id, [
                'deleted_status_id' => $status->id,
            ]);

            return response()->json([
                'message' => 'Status deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function setBusy(Request $request)
    {
        try {
            $user = auth()->user();
            $validated = $request->validate([
                'status_text' => 'nullable|string|max:150',
                'clear_after' => 'nullable|date|after_or_equal:now',
            ]);

            $this->deleteExpiredStatus($user->id);

            $status = UserStatus::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'is_busy' => true,
                    'status_text' => $validated['status_text'] ?? 'Busy',
                    'clear_after' => $validated['clear_after'] ?? null,
                ]
            );

            Log::info('User set as busy: ' . $user->id);

            return response()->json([
                'message' => 'Status set to busy',
                'data' => new UserStatusesResource($status),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error setting busy status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to set busy status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function setAvailable()
    {
        try {
            $user = auth()->user();

            $status = UserStatus::where('user_id', $user->id)->first();

            if (!$status) {
                return response()->json([
                    'message' => 'No status found',
                ], 404);
            }

            $status->update([
                'is_busy' => false,
                'clear_after' => null,
            ]);

            Log::info('User set as available: ' . $user->id);

            return response()->json([
                'message' => 'Status set to available',
                'data' => new UserStatusesResource($status),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error setting available status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to set available status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    private function deleteExpiredStatus($userId)
    {
        try {
            $expiredStatus = UserStatus::where('user_id', $userId)
                ->where('clear_after', '<=', now())
                ->where('clear_after', '!=', null)
                ->first();

            if ($expiredStatus) {
                $expiredStatus->delete();
                Log::info('Expired status deleted for user ID: ' . $userId);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting expired status: ' . $e->getMessage());
        }
    }

    public function clearExpiredStatuses()
    {
        try {
            $count = UserStatus::where('clear_after', '<=', now())
                ->where('clear_after', '!=', null)
                ->delete();

            Log::info('Cleared ' . $count . ' expired statuses');

            return response()->json([
                'message' => 'Expired statuses cleared',
                'deleted_count' => $count,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error clearing expired statuses: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to clear expired statuses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
