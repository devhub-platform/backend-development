<?php

namespace App\Services\AI;

use App\Models\GeneratedPostImage;
use App\Services\ImageUploadCloudinaryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostAIImageService
{
    public function __construct(
        private HackClubImageService         $imageGenerator,
        private ImageUploadCloudinaryService $cloudinary,
    ) {}

    /**
     * Generate an image from a prompt and upload it to Cloudinary.
     *
     * The image is stored in generated_post_images as "pending" —
     * NOT attached to any post yet. The user must call confirm() to use it.
     * If the user never confirms, the cleanup command will delete it later.
     *
     * @throws \Exception if generation or upload fails.
     */
    public function generate(string $prompt, int $userId, ?string $model = null): GeneratedPostImage
    {
        // 1. Call HackClub Nanobanana → get base64
        $base64 = $this->imageGenerator->generateBase64($prompt, $model);

        // 2. Convert base64 to a temp file and upload to Cloudinary
        $imageData = base64_decode($base64);
        $tempPath  = sys_get_temp_dir() . '/ai_img_' . Str::uuid() . '.png';

        try {
            file_put_contents($tempPath, $imageData);

            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                'ai-generated.png',
                'image/png',
                null,
                true
            );

            $secureUrl = $this->cloudinary->uploadImage(
                $uploadedFile,
                'ai-generated-covers',
                'ai_' . $userId . '_' . time()
            );

        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        $publicId = $this->extractPublicId($secureUrl);

        // 3. Store as pending — not attached to any post yet
        return GeneratedPostImage::create([
            'user_id'    => $userId,
            'prompt'     => $prompt,
            'secure_url' => $secureUrl,
            'public_id'  => $publicId,
            'status'     => 'pending',
        ]);
    }

    /**
     * Confirm usage: attach the generated image to a post.
     * Returns the secure URL to store in posts.cover_image.
     *
     * @throws \Exception if the image does not belong to this user.
     */
    public function confirm(int $generatedImageId, int $postId, int $userId): string
    {
        $image = GeneratedPostImage::where('id', $generatedImageId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->firstOrFail();

        $image->update([
            'post_id' => $postId,
            'status'  => 'confirmed',
        ]);

        return $image->secure_url;
    }

    /**
     * Discard a generated image — deletes from Cloudinary and DB.
     * Called when the user explicitly rejects the generated image.
     */
    public function discard(int $generatedImageId, int $userId): void
    {
        $image = GeneratedPostImage::where('id', $generatedImageId)->first();

        if (!$image) {
            throw new \Exception('Image not found.');
        }

        if ($image->user_id !== $userId) {

            throw new \Exception('You do not have permission to delete this image.', 403);
        }

        if ($image->public_id) {
            $this->cloudinary->deleteImage($image->public_id);
        }

        $image->delete();
    }

    private function extractPublicId(string $url): ?string
    {
        if (preg_match('/\/v\d+\/(.+)\.[a-zA-Z]+$/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\/upload\/(.+)\.[a-zA-Z]+$/', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
