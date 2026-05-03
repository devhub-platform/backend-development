<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Pagination\Paginator;

class FeedbackService
{
    /**
     * Create feedback from a user
     */
    public function createFeedback(User $user, array $data): array
    {
        try {
            $attachments = [];

            if (!empty($data['attachment'])) {
                $attachments[] = [
                    'url' => $data['attachment'],
                    'uploaded_at' => now()->toIso8601String(),
                ];
            }

            $feedback = Feedback::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'] ?? 'other',
                'rating' => $data['rating'] ?? null,
                'attachments' => !empty($attachments) ? $attachments : null,
            ]);

            return [
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedback' => $feedback,
                'status' => 201,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to submit feedback: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Get user's feedback
     */
    public function getUserFeedback(User $user, int $perPage = 15): Paginator
    {
        return Feedback::where('user_id', $user->id)
            ->latest()
            ->simplePaginate($perPage);
    }

    /**
     * Get feedback details
     */
    public function getFeedbackDetails(int $feedbackId, User $user): array
    {
        $feedback = Feedback::find($feedbackId);

        if (!$feedback) {
            return [
                'success' => false,
                'message' => 'Feedback not found',
                'status' => 404,
            ];
        }

        // User can only view their own feedback unless they're admin
        if ($feedback->user_id !== $user->id && !$user->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized to view this feedback',
                'status' => 403,
            ];
        }

        return [
            'success' => true,
            'feedback' => $feedback,
            'status' => 200,
        ];
    }

    /**
     * Update feedback status (admin only)
     */
    public function updateFeedbackStatus(int $feedbackId, string $status, User $admin, ?string $response = null): array
    {
        if (!$admin->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only admins can update feedback status',
                'status' => 403,
            ];
        }

        $validStatuses = ['new', 'reviewed', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
                'status' => 422,
            ];
        }

        $feedback = Feedback::find($feedbackId);

        if (!$feedback) {
            return [
                'success' => false,
                'message' => 'Feedback not found',
                'status' => 404,
            ];
        }

        $feedback->update([
            'status' => $status,
            'admin_response' => $response,
            'responded_by' => $admin->id,
            'responded_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Feedback status updated successfully',
            'feedback' => $feedback,
            'status' => 200,
        ];
    }

    /**
     * Get all feedback (admin only)
     */
    public function getAllFeedback(User $admin, ?string $status = null, ?string $type = null, int $perPage = 15): array
    {
        if (!$admin->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only admins can view all feedback',
                'status' => 403,
            ];
        }

        $query = Feedback::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $feedbacks = $query->with('user', 'respondedBy')
            ->latest()
            ->simplePaginate($perPage);

        return [
            'success' => true,
            'feedbacks' => $feedbacks,
            'status' => 200,
        ];
    }

    /**
     * Get feedback statistics (admin only)
     */
    public function getFeedbackStats(User $admin): array
    {
        if (!$admin->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only admins can view feedback statistics',
                'status' => 403,
            ];
        }

        return [
            'success' => true,
            'stats' => [
                'total' => Feedback::count(),
                'by_type' => Feedback::selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_status' => Feedback::selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'average_rating' => Feedback::whereNotNull('rating')->avg('rating'),
                'resolved' => Feedback::where('status', 'resolved')->count(),
                'pending' => Feedback::whereIn('status', ['new', 'reviewed', 'in_progress'])->count(),
            ],
            'status' => 200,
        ];
    }

    /**
     * Delete feedback (user can delete their own, admin can delete any)
     */
    public function deleteFeedback(int $feedbackId, User $user): array
    {
        $feedback = Feedback::find($feedbackId);

        if (!$feedback) {
            return [
                'success' => false,
                'message' => 'Feedback not found',
                'status' => 404,
            ];
        }

        // User can only delete their own feedback unless they're admin
        if ($feedback->user_id !== $user->id && !$user->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized to delete this feedback',
                'status' => 403,
            ];
        }

        $feedback->delete();

        return [
            'success' => true,
            'message' => 'Feedback deleted successfully',
            'status' => 200,
        ];
    }
}

