<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\FeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Services\FeedbackService;
use App\Services\HackClubCdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedbackController
{
    private FeedbackService $feedbackService;
    private HackClubCdnService $hackClubCdnService;

    public function __construct(
        FeedbackService $feedbackService,
        HackClubCdnService $hackClubCdnService
    )
    {
        $this->feedbackService = $feedbackService;
        $this->hackClubCdnService = $hackClubCdnService;
    }

    /**
     * Create feedback
     */
    public function store(FeedbackRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validated();

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            try {
                $attachmentUrl = $this->hackClubCdnService->uploadFileUrl($request->file('attachment'));
                $validated['attachment'] = $attachmentUrl;
            } catch (\Exception $e) {
                Log::warning("Failed to upload feedback attachment for user {$user->id}: {$e->getMessage()}");
                return response()->json([
                    'message' => 'Failed to upload attachment. Please try again.',
                    'error' => $e->getMessage()
                ], 422);
            }
        } else {
            unset($validated['attachment']);
        }

        $result = $this->feedbackService->createFeedback($user, $validated);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => 'your feedback has been submitted successfully',
            'data' => new FeedbackResource($result['feedback']),
        ], $result['status']);
    }

    /**
     * Get user's feedback
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $perPage = $request->query('per_page', 15);

        $feedbacks = $this->feedbackService->getUserFeedback($user, $perPage);

        return response()->json([
            'message' => 'User feedback retrieved successfully',
            'data' => FeedbackResource::collection($feedbacks),
            'pagination' => [
                'current_page' => $feedbacks->currentPage(),
                'per_page' => $feedbacks->perPage(),
                'total' => $feedbacks->total(),
                'path' => $feedbacks->path(),
            ],
        ], 200);
    }

    /**
     * Get feedback details
     */
    public function show(Request $request, int $feedbackId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->feedbackService->getFeedbackDetails($feedbackId, $user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => 'Feedback retrieved successfully',
            'data' => new FeedbackResource($result['feedback']),
        ], $result['status']);
    }

    /**
     * Delete feedback
     */
    public function destroy(Request $request, int $feedbackId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->feedbackService->deleteFeedback($feedbackId, $user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json(['message' => $result['message']], $result['status']);
    }

    /**
     * Get all feedback (Admin only)
     */
    public function getAllFeedback(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $status = $request->query('status');
        $type = $request->query('type');
        $perPage = $request->query('per_page', 15);

        $result = $this->feedbackService->getAllFeedback($user, $status, $type, $perPage);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => 'All feedback retrieved successfully',
            'data' => FeedbackResource::collection($result['feedbacks']),
            'pagination' => [
                'current_page' => $result['feedbacks']->currentPage(),
                'per_page' => $result['feedbacks']->perPage(),
                'total' => $result['feedbacks']->total(),
                'path' => $result['feedbacks']->path(),
            ],
        ], $result['status']);
    }

    /**
     * Update feedback status (Admin only)
     */
    public function updateStatus(Request $request, int $feedbackId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:new,reviewed,in_progress,resolved,closed',
            'response' => 'nullable|string|max:5000',
        ]);

        $result = $this->feedbackService->updateFeedbackStatus(
            $feedbackId,
            $validated['status'],
            $user,
            $validated['response'] ?? null
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new FeedbackResource($result['feedback']),
        ], $result['status']);
    }

    /**
     * Get feedback statistics (Admin only)
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->feedbackService->getFeedbackStats($user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => 'Feedback statistics retrieved successfully',
            'data' => $result['stats'],
        ], $result['status']);
    }
}



