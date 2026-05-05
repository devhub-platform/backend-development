<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddPostToAI
{
    public function addPostToModel($post): bool
    {
        try {
            $post->loadMissing('user', 'tags');

            $baseUrl = config('services.ai_main_model.base_url');

            if (empty($baseUrl)) {
                Log::warning('AI_MAIN_MODEL_BASE_URL is not configured');
                return false;
            }

            $tagsData = $post->tags ?? [];
            $tagsString = '';
            if (is_array($tagsData)) {
                $tagsString = implode(',', array_map(fn($tag) => $tag->name ?? (string)$tag, $tagsData));
            } elseif ($tagsData instanceof \Illuminate\Database\Eloquent\Collection) {
                $tagsString = $tagsData->pluck('name')->implode(',');
            }

            $payload = [
                'title' => (string)$post->title,
                'url' => (string)($post->url ?? ''),
                'author' => (string)($post->user->name ?? 'Unknown'),
                'category' => 'General',
                'tags' => $tagsString,
                'content' => (string)($post->content ?? ''),
            ];

            Log::info('Adding post to AI model', ['post_id' => $post->id, 'payload' => $payload]);

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->post(
                    rtrim($baseUrl, '/') . '/add_article',
                    $payload
                );

            if (!$response->successful()) {
                Log::error('Failed to add post to AI model', [
                    'post_id' => $post->id,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            Log::info('Successfully added post to AI model', ['post_id' => $post->id]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AI add_article request failed', [
                'post_id' => $post->id ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function updatePostToModel($post): bool
    {
        try {
            $post->loadMissing('user', 'tags');

            $baseUrl = config('services.ai_main_model.base_url');

            if (empty($baseUrl)) {
                Log::warning('AI_MAIN_MODEL_BASE_URL is not configured');
                return false;
            }

            if (empty($post->id)) {
                Log::error('Post ID is required for update', ['post' => $post]);
                return false;
            }

            $tagsData = $post->tags ?? [];
            $tagsString = '';
            if (is_array($tagsData)) {
                $tagsString = implode(',', array_map(fn($tag) => $tag->name ?? (string)$tag, $tagsData));
            } elseif ($tagsData instanceof \Illuminate\Database\Eloquent\Collection) {
                $tagsString = $tagsData->pluck('name')->implode(',');
            }

            $payload = [
                'title' => (string)$post->title,
                'url' => (string)($post->url ?? ''),
                'author' => (string)($post->user->name ?? 'Unknown'),
                'category' => 'General',
                'tags' => $tagsString,
                'content' => (string)($post->content ?? ''),
            ];

            Log::info('Updating post in AI model', ['post_id' => $post->id, 'payload' => $payload]);

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->put(
                    rtrim($baseUrl, '/') . '/update_article/' . $post->id,
                    $payload
                );

            if (!$response->successful()) {
                Log::error('Failed to update post in AI model', [
                    'post_id' => $post->id,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            Log::info('Successfully updated post in AI model', ['post_id' => $post->id]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AI update_article request failed', [
                'post_id' => $post->id ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function deletePostFromModel($post): bool
    {
        try {
            $baseUrl = config('services.ai_main_model.base_url');

            if (empty($baseUrl)) {
                Log::warning('AI_MAIN_MODEL_BASE_URL is not configured');
                return false;
            }

            $postId = is_object($post) ? $post->id : $post;

            if (empty($postId)) {
                Log::error('Post ID is required for delete', ['post' => $post]);
                return false;
            }

            Log::info('Deleting post from AI model', ['post_id' => $postId]);

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->delete(
                    rtrim($baseUrl, '/') . '/delete_article/' . $postId
                );

            if (!$response->successful()) {
                Log::error('Failed to delete post from AI model', [
                    'post_id' => $postId,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            Log::info('Successfully deleted post from AI model', ['post_id' => $postId]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AI delete_article request failed', [
                'post_id' => $postId ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
