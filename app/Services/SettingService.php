<?php

namespace App\Services;

use App\Mail\PasswordUpdatedSuccessfullyMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function updatePassword(User $user, string $currentPassword, string $newPassword): array
    {
        if (!Hash::check($currentPassword, $user->password)) {
            Log::warning("Failed password update attempt for user: {$user->email} - incorrect current password");
            return [
                'success' => false,
                'message' => 'Current password is incorrect',
                'status' => 400,
            ];
        }

        try {
            $user->update(['password' => Hash::make($newPassword)]);

            Mail::to($user->email)->send(new PasswordUpdatedSuccessfullyMail($user));
            Log::info("Password updated successfully for user: {$user->email}");

            return [
                'success' => true,
                'message' => "Hi {$user->name}, your password has been updated successfully",
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Password update failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update password. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function softDeleteAccount(User $user): array
    {
        try {
            $email = $user->email;
            $user->delete();

            Log::info("User soft deleted: {$email}");

            return [
                'success' => true,
                'message' => 'Profile deleted successfully (soft delete)',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Profile deletion failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to delete profile. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function forceDeleteAccount(User $user): array
    {
        try {
            $email = $user->email;
            $userId = $user->id;

            if ($user->avatar_url) {
                $this->deleteFileFromStorage($user->avatar_url);
            }

            // Delete cover image if exists
            if ($user->cover_image) {
                $this->deleteFileFromStorage($user->cover_image);
            }

            // Delete user and all related data via cascade
            $user->forceDelete();

            Log::warning("User permanently deleted: {$email} (ID: {$userId})");

            return [
                'success' => true,
                'message' => 'Profile permanently deleted successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Permanent profile deletion failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to permanently delete profile. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Update user social accounts
     */
    public function updateSocialAccounts(User $user, array $data): array
    {
        try {
            $user->update($data);

            Log::info("Social accounts updated for user: {$user->email}", $data);

            return [
                'success' => true,
                'message' => 'Social accounts updated successfully',
                'user' => $user->fresh(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Social accounts update failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update social accounts. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Update user profile information
     */
    public function updateProfile(User $user, array $data): array
    {
        try {
            $user->update($data);

            Log::info("Profile updated for user: {$user->email}");

            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Profile update failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(User $user, string $avatarUrl): array
    {
        try {
            // Delete old avatar if exists
            if ($user->avatar_url) {
                $this->deleteFileFromStorage($user->avatar_url);
            }

            $user->update(['avatar_url' => $avatarUrl]);

            Log::info("Avatar updated for user: {$user->email}");

            return [
                'success' => true,
                'message' => 'Avatar updated successfully',
                'user' => $user->fresh(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Avatar update failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update avatar. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Update user cover image
     */
    public function updateCoverImage(User $user, string $coverUrl): array
    {
        try {
            // Delete old cover image if exists
            if ($user->cover_image) {
                $this->deleteFileFromStorage($user->cover_image);
            }

            $user->update(['cover_image' => $coverUrl]);

            Log::info("Cover image updated for user: {$user->email}");

            return [
                'success' => true,
                'message' => 'Cover image updated successfully',
                'user' => $user->fresh(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Cover image update failed for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update cover image. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(User $user): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Notification preferences retrieved successfully',
                'preferences' => $user->getNotificationPreferences(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get notification preferences for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to get notification preferences',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Update notification preference
     */
    public function updateNotificationPreference(User $user, string $type, bool $enabled): array
    {
        try {
            // Validate preference type
            $validTypes = ['new_follower', 'new_comment', 'new_reaction', 'new_post_from_following', 'mention'];
            if (!in_array($type, $validTypes)) {
                return [
                    'success' => false,
                    'message' => "Invalid notification preference type: {$type}",
                    'status' => 400,
                ];
            }

            $user->updateNotificationPreference($type, $enabled);

            Log::info("Notification preference updated for user: {$user->email} - {$type}: {$enabled}");

            return [
                'success' => true,
                'message' => 'Notification preference updated successfully',
                'preferences' => $user->getNotificationPreferences(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to update notification preference for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update notification preference',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function updateMultiplePreferences(User $user, array $preferences): array
    {
        try {
            $validTypes = ['new_follower', 'new_comment', 'new_reaction', 'new_post_from_following', 'mention'];

            foreach ($preferences as $type => $enabled) {
                if (!in_array($type, $validTypes)) {
                    return [
                        'success' => false,
                        'message' => "Invalid notification preference type: {$type}",
                        'status' => 400,
                    ];
                }
                $user->updateNotificationPreference($type, $enabled);
            }

            Log::info("Multiple notification preferences updated for user: {$user->email}");

            return [
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'preferences' => $user->getNotificationPreferences(),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to update notification preferences for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getAccountSettings(User $user): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Account settings retrieved successfully',
                'settings' => [
                    'email' => $user->email,
                    'email_verified' => (bool)$user->email_verified_at,
                    'alt_email' => !$user->isLoginViaAltEmail() ? $user->alt_email : null,
                    'alt_email_verified' => !$user->isLoginViaAltEmail() ? (bool)$user->alt_email_verified_at : null,
                    'has_password' => (bool)$user->password,
                    'two_factor_enabled' => (bool)$user->two_factor_expires_at,
                    'social_accounts' => [
                        'linkedin' => (bool)$user->linkedin_username,
                        'github' => (bool)$user->github_username,
                        'orcid' => (bool)$user->orcid_username,
                    ],
                    'notification_preferences' => $user->getNotificationPreferences(),
                    'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                ],
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get account settings for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to get account settings',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }


    public function canModifyAccount(User $user): bool
    {
        return !$user->deleted_at;
    }


    private function deleteFileFromStorage(string $fileUrl): void
    {
        try {
            $fileName = basename($fileUrl);
            Storage::disk('s3')->delete($fileName);
        } catch (\Exception $e) {
            Log::warning("Failed to delete file from storage: {$fileUrl} - {$e->getMessage()}");
        }
    }
}

