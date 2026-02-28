<?php

namespace App\Services;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageUploadCloudinaryService
{

    public function uploadAvatar(User $user, UploadedFile $image): string
    {
        if ($user->avatar_url && Str::contains($user->avatar_url, 'cloudinary')) {
            $this->deleteImage($user->avatar_url);
        }

        return $this->uploadImage($image, 'avatars', $user->username);
    }

    public function uploadCoverImage(User $user, UploadedFile $image): string
    {
        if ($user->cover_image && Str::contains($user->cover_image, 'cloudinary')) {
            $this->deleteImage($user->cover_image);
        }

        return $this->uploadImage($image, 'cover_images', $user->username);
    }

    public function uploadPostCoverImage(UploadedFile $image, string $postSlug): string
    {
        return $this->uploadImage($image, 'posts', $postSlug);
    }

    public function uploadFile(UploadedFile $file, string $folder, ?string $customName = null): string
    {
        try {
            $fileName = $customName ?? Str::uuid()->toString();

            $response = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'public_id' => $fileName . '_' . time(),
                'overwrite' => true,
                'resource_type' => 'auto',
            ]);

            if (isset($response['secure_url'])) {
                Log::info("File uploaded successfully to Cloudinary: {$response['secure_url']}");
                return $response['secure_url'];
            }

            throw new \Exception('Failed to get secure URL from Cloudinary response');
        } catch (\Exception $e) {
            Log::error("Cloudinary file upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function uploadImage(UploadedFile $image, string $folder, ?string $customName = null): string
    {
        try {
            $fileName = $customName ?? Str::uuid()->toString();
            $publicId = $folder . '/' . $fileName . '_' . time();

            $response = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                'folder' => $folder,
                'public_id' => $fileName . '_' . time(),
                'overwrite' => true,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ]
            ]);

            if (isset($response['secure_url'])) {
                Log::info("Image uploaded successfully to Cloudinary: {$response['secure_url']}");
                return $response['secure_url'];
            }

            throw new \Exception('Failed to get secure URL from Cloudinary response');
        } catch (\Exception $e) {
            Log::error("Cloudinary upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function uploadImageAlternative(UploadedFile $image, string $folder): string
    {
        try {
            $uploadedFile = $image->storeOnCloudinary($folder);
            $url = $uploadedFile->getSecurePath();

            Log::info("Image uploaded successfully to Cloudinary: {$url}");
            return $url;
        } catch (\Exception $e) {
            Log::error("Cloudinary upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteImage(string $imageUrl): bool
    {
        try {
            $publicId = $this->extractPublicId($imageUrl);

            if (!$publicId) {
                Log::warning("Could not extract public ID from URL: {$imageUrl}");
                return false;
            }

            $result = cloudinary()->uploadApi()->destroy($publicId);

            if ($result['result'] === 'ok') {
                Log::info("Image deleted successfully from Cloudinary: {$publicId}");
                return true;
            }

            Log::warning("Cloudinary delete returned unexpected result: " . json_encode($result));
            return false;
        } catch (\Exception $e) {
            Log::error("Cloudinary delete failed: " . $e->getMessage());
            return false;
        }
    }


    private function extractPublicId(string $url): ?string
    {
        $pattern = '/\/v\d+\/(.+)\.[a-zA-Z]+$/';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        $pattern2 = '/\/upload\/(.+)\.[a-zA-Z]+$/';

        if (preg_match($pattern2, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getOptimizedUrl(string $publicId, array $options = []): string
    {
        $defaultOptions = [
            'width' => $options['width'] ?? null,
            'height' => $options['height'] ?? null,
            'crop' => $options['crop'] ?? 'fill',
            'quality' => $options['quality'] ?? 'auto',
            'fetch_format' => 'auto',
        ];

        $transformations = array_filter($defaultOptions);

        return cloudinary()->image($publicId)->toUrl($transformations);
    }

    public function getAvatarUrl(string $publicId, int $size = 150): string
    {
        return $this->getOptimizedUrl($publicId, [
            'width' => $size,
            'height' => $size,
            'crop' => 'fill',
            'gravity' => 'face',
        ]);
    }

    public function getCoverImageUrl(string $publicId, int $width = 1200, int $height = 400): string
    {
        return $this->getOptimizedUrl($publicId, [
            'width' => $width,
            'height' => $height,
            'crop' => 'fill',
        ]);
    }
}
