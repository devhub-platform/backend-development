<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{

    public function uploadAvatar(User $user, UploadedFile $image): string
    {
        try {

            $filename = $this->generateFileName('avatar', $user->username);
            $extension = $image->getClientOriginalExtension();
            $fullFilename = $filename . '.' . $extension;

            $path = $image->storeAs('avatars-images', $fullFilename, 's3');

            if ($user->avatar_url) {
                $this->deleteImage($user->avatar_url, 'avatars-images');
            }

            $imageUrl = Storage::url($path);

            $user->update(['avatar_url' => $imageUrl]);

            Log::info("Avatar uploaded for user: {$user->email}", [
                'filename' => $fullFilename,
                'path' => $path,
            ]);

            return $imageUrl;
        } catch (\Exception $e) {
            Log::error("Avatar upload failed for user: {$user->email}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function uploadCoverImage(User $user, UploadedFile $image): string
    {
        try {
            $filename = $this->generateFileName('cover', $user->username);
            $extension = $image->getClientOriginalExtension();
            $fullFilename = $filename . '.' . $extension;

            $path = $image->storeAs('covers-profiles', $fullFilename, 's3');

            if ($user->cover_image) {
                $this->deleteImage($user->cover_image, 'covers-profiles');
            }

            $imageUrl = Storage::url($path);

            $user->update(['cover_image' => $imageUrl]);

            Log::info("Cover image uploaded for user: {$user->email}", [
                'filename' => $fullFilename,
                'path' => $path,
            ]);

            return $imageUrl;
        } catch (\Exception $e) {
            Log::error("Cover image upload failed for user: {$user->email}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function deleteImage(string $imageUrl, string $directory): bool
    {
        try {

            $filename = basename($imageUrl);
            $path = $directory . '/' . $filename;

            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
                Log::info("Image deleted from S3: {$path}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::warning("Failed to delete image from S3: {$imageUrl}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function generateFileName(string $type, string $username): string
    {
        $slug = Str::slug($username);
        $timestamp = time();
        return "{$type}-{$slug}-{$timestamp}";
    }
}
