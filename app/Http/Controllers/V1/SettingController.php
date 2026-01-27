<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\PasswordUpdatedSuccessfullyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class SettingController
{
    use AuthorizesRequests;
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $validated = $request->validated();
        try {
            $this->authorize('update', Auth::user());
            $user = Auth::user();
            $name = $user->name;

            if (!Hash::check($request->current_password, $user->password)) {
                Log::warning("Failed password update attempt for user: {$user->email} - incorrect current password");
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            Mail::to($user->email)->send(new PasswordUpdatedSuccessfullyMail($user));

            Log::info("Password updated successfully for user: {$user->email}");

            return response()->json([
                'message' => "Hi $name, your password has been updated successfully"
            ], 200);
        } catch (JWTException $e) {
            Log::error("JWT exception during password update for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update password',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error("Password update failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update password. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete() // permanently delete user
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $email = $user->email;
            $userId = $user->id;

            if ($user->avatar_url) {
                Storage::disk('s3')->delete(basename($user->avatar_url));
            }
            if ($user->cover_image) {
                Storage::disk('s3')->delete(basename($user->cover_image));
            }

            $user->forceDelete();

            Log::warning('User permanently deleted: ' . $email . ' (ID: ' . $userId . ')');

            return response()->json([
                'message' => 'Profile permanently deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Permanent profile deletion failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to permanently delete profile. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete() // soft delete user can be restored later
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $email = $user->email;
            $user->delete();

            Log::info('User soft deleted: ' . $email);

            return response()->json([
                'message' => 'Profile deleted successfully (soft delete)',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Profile deletion failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete profile. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addSocialAccounts(Request $request)
    {
        $request->validate([
            'linkedin_username' => 'sometimes|nullable|string|max:255|regex:/^[a-zA-Z0-9\-]+$/',
            'github_username' => 'sometimes|nullable|string|max:255|regex:/^[a-zA-Z0-9\-]+$/',
        ], [
            'linkedin_username.regex' => 'LinkedIn username format is invalid',
            'github_username.regex' => 'GitHub username format is invalid',
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $data = $request->only(['linkedin_username', 'github_username']);
            $user->update($data);

            Log::info("Social accounts updated for user: {$user->email}", $data);

            return response()->json([
                'message' => 'Social accounts updated successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Social accounts update failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update social accounts. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

