<?php

namespace App\Http\Controllers\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostAI\ConfirmImageRequest;
use App\Http\Requests\PostAI\GenerateContentRequest;
use App\Http\Requests\PostAI\GenerateImageRequest;
use App\Models\Post;
use App\Services\AI\PostAIImageService;
use App\Services\AI\PostContentGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostAIController extends Controller
{
    public function __construct(
        private PostAIImageService          $imageService,
        private PostContentGeneratorService $contentService,
    ) {}

    /**
     * Generate a cover image for a post using HackClub Nanobanana.
     *
     * The image is uploaded to Cloudinary and stored as "pending".
     * It is NOT attached to any post until the user calls confirm().
     *
     * POST /v1/posts/ai/generate-image
     */
    public function generateImage(GenerateImageRequest $request): JsonResponse
    {
        try {
            $generated = $this->imageService->generate(
                prompt: $request->input('prompt'),
                userId: $request->user()->id,
            );

            return response()->json([
                'success'            => true,
                'generated_image_id' => $generated->id,
                'secure_url'         => $generated->secure_url,
                'message'            => 'Image generated successfully. Call /confirm to attach it to your post.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm usage of a generated image and attach it to a post.
     * Stores the secure_url in posts.cover_image.
     *
     * POST /v1/posts/ai/confirm-image
     */
    public function confirmImage(ConfirmImageRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $postId = $request->input('post_id');

        // Security: post must belong to this user
        $post = Post::where('id', $postId)
            ->where('user_id', $userId)
            ->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or does not belong to you.',
            ], 403);
        }

        try {
            $secureUrl = $this->imageService->confirm(
                generatedImageId: $request->input('generated_image_id'),
                postId:           $postId,
                userId:           $userId,
            );

            $post->update(['cover_image' => $secureUrl]);

            return response()->json([
                'success'     => true,
                'cover_image' => $secureUrl,
                'message'     => 'Cover image attached to post successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Discard a generated image — deletes it from Cloudinary and DB.
     * Call this when the user rejects the generated image.
     *
     * DELETE /v1/posts/ai/generated-images/{id}
     */
    public function discardImage(Request $request, int $generatedImageId): JsonResponse
    {
        $this->imageService->discard($generatedImageId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Generated image discarded successfully.',
        ]);
    }

    /**
     * Generate post content using the Llama model.
     * Content is returned only — never auto-saved to the database.
     *
     * POST /v1/posts/ai/generate-content
     */
    public function generateContent(GenerateContentRequest $request): JsonResponse
    {
        try {
            $content = $this->contentService->generate(
                prompt: $request->input('prompt'),
            );

            return response()->json([
                'success' => true,
                'content' => $content,
                'message' => 'Content generated. Review and save it manually.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Content generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
