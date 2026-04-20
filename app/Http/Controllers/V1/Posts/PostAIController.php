<?php

namespace App\Http\Controllers\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostAI\GenerateContentRequest;
use App\Http\Requests\PostAI\GenerateImageRequest;
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
     * Image is uploaded to Cloudinary and stored as "pending".
     * Pass generated_image_id when creating the post to attach it automatically.
     *
     * POST /v1/posts/ai/generate-image
     */
    public function generateImage(GenerateImageRequest $request): JsonResponse
    {
        try {
            $generated = $this->imageService->generate(
                prompt: $request->input('prompt'),
                userId: $request->user()->id,
                model:  $request->input('model'),
            );

            return response()->json([
                'success'            => true,
                'generated_image_id' => $generated->id,
                'secure_url'         => $generated->secure_url,
                'message'            => 'Image generated. Pass generated_image_id when creating your post.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Discard a generated image — deletes from Cloudinary and DB.
     *
     * DELETE /v1/posts/ai/generated-images/{id}
     */
    public function discardImage(Request $request, int $generatedImageId): JsonResponse
    {
        try {
            $this->imageService->discard($generatedImageId, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Generated image discarded successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() === 403 ? 403 : 500);
        }
    }

    /**
     * Generate post content using HackClub AI (Gemini 2.5 Flash).
     * Detects intent from prompt — content only, title only, or both.
     * Content is returned only — never auto-saved to the database.
     *
     * POST /v1/posts/ai/generate-content
     *
     * Request body:
     *   prompt  (required) string — topic, idea, or instruction
     *   length  (optional) string — short | medium | long (default: medium)
     */
    public function generateContent(GenerateContentRequest $request): JsonResponse
    {
        try {
            $result = $this->contentService->generate(
                prompt: $request->input('prompt'),
                length: $request->input('length', 'medium'),
            );

            return response()->json([
                'success' => true,
                'content' => $result['content'],
                'title'   => $result['title'] ?? null,
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
