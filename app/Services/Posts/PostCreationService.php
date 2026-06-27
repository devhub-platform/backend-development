<?php

namespace App\Services\Posts;

use App\Models\Post;
use App\Models\Tag;
use App\Notifications\NewPostNotification;
use App\Services\AI\PostAIImageService;
use App\Services\HackClubCdnService;
use App\Services\ImageUploadCloudinaryService;
use App\Services\ModerationService;
use App\Services\OneSignalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use OneSignal;

class PostCreationService
{
    public function __construct(
        private ModerationService            $moderationService,
        private ImageUploadCloudinaryService $cloudinaryService,
        private HackClubCdnService           $hackClubCdnService,
        private PostAIImageService           $aiImageService,
        private OneSignalService             $oneSignalService,
    )
    {
    }

    public function create(
        int                     $userId,
        string                  $authorName,
        string                  $authorPlayerId,
        array                   $validated,
        ?UploadedFile           $coverImage = null,
        array|UploadedFile|null $images = null,
        ?array                  $requestedTags = null,
    ): array
    {
        $requestedTags = $requestedTags ?? ($validated['tags'] ?? []);
        unset($validated['tags']);

        $validated['user_id'] = $userId;
        $validated['slug'] = Str::slug($validated['title']) . '-' . random_int(1000, 999999);
        if ($coverImage) {
            $validated['cover_image'] = $this->cloudinaryService->uploadPostCoverImage(
                $coverImage,
                $validated['slug']
            );
        }

        if ($images) {
            $validated['image_url'] = $this->uploadPostImages($images);
        }

        $contentToModerate = ($validated['content'] ?? '') . ' ' . ($validated['title'] ?? '');
        $moderationResult = $this->moderationService->moderateContent($contentToModerate);

        if ($moderationResult['flagged'] ?? false) {
            $reasons = $this->moderationService->getModerationMessage($moderationResult);
            Log::warning("Post content flagged for user ID: {$userId}", ['reasons' => $reasons]);

            if (!empty($authorPlayerId)) {
                OneSignal::sendNotificationToUser(
                    'A user attempted to create a post that violates content policies reason: ' . $reasons,
                    $authorPlayerId,
                    null,
                    null,
                    null,
                    'Content Violation Attempt'
                );
            }

            return [
                'ok' => false,
                'reasons' => $reasons,
            ];
        }

        $generatedImageId = $validated['generated_image_id'] ?? null;
        unset($validated['generated_image_id']);

        $post = Post::create($validated);

        $tagIds = collect($requestedTags)
            ->map(fn($tagName) => trim((string)$tagName))
            ->filter()
            ->unique()
            ->map(fn($tagName) => Tag::firstOrCreate(['name' => $tagName])->id)
            ->values();

        if ($tagIds->isNotEmpty()) {
            $post->tags()->syncWithoutDetaching($tagIds);
        }

        if ($generatedImageId) {
            try {
                $secureUrl = $this->aiImageService->confirm(
                    generatedImageId: $generatedImageId,
                    postId: $post->id,
                    userId: $userId,
                );
                $post->update(['cover_image' => $secureUrl]);
            } catch (\Throwable $e) {
                Log::warning('Could not attach generated image to post', [
                    'post_id' => $post->id,
                    'generated_image_id' => $generatedImageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (($validated['status'] ?? 'draft') !== 'draft') {
            $followers = auth()->user()?->followers ?? collect();

            if ($followers->isNotEmpty()) {
                Notification::send($followers, new NewPostNotification($post->load('user')));

                $playerIds = $followers->pluck('onesignal_player_id')->filter()->all();

                if (!empty($playerIds)) {
                    $this->oneSignalService->sendToUsers(
                        message: Str::limit((string)$post->content, 100, '...'),
                        playerIds: $playerIds,
                        heading: 'New post from ' . $authorName,
                    );
                }
            }
        }

        return [
            'ok' => true,
            'post' => $post,
        ];
    }

    private function uploadPostImages(array|UploadedFile $files): array
    {
        $files = is_array($files) ? $files : [$files];

        return collect($files)
            ->filter(fn($file) => $file instanceof UploadedFile)
            ->map(fn(UploadedFile $file) => $this->hackClubCdnService->uploadFileUrl($file))
            ->filter()
            ->values()
            ->all();
    }
}

