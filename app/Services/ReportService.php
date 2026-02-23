<?php

namespace App\Services;

use App\Mail\SupportReportMail;
use App\Models\Report;
use App\Models\User;
use App\Notifications\UserReportedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ReportService
{
    private const REPORT_REASONS = [
        'spam' => 'Spam or misleading',
        'harassment' => 'Harassment or bullying',
        'hate_speech' => 'Hate speech or discrimination',
        'violence' => 'Violence or dangerous content',
        'adult_content' => 'Adult or explicit content',
        'copyright' => 'Copyright violation',
        'misinformation' => 'False information',
        'other' => 'Other',
    ];


    private const ADMIN_EMAIL = 'youssef.ahmed.fci@gmail.com';

    public function blockUser(User $blocker, User $target): array
    {
        if ($blocker->id === $target->id) {
            return [
                'success' => false,
                'message' => 'Cannot block yourself',
                'status' => 400,
            ];
        }

        if ($blocker->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            return [
                'success' => false,
                'message' => "User {$target->name} is already blocked",
                'status' => 400,
            ];
        }

        try {
            $blocker->blockedUsers()->attach($target->id);

            Log::info("User {$blocker->email} blocked user {$target->email}");

            return [
                'success' => true,
                'message' => "User {$target->name} blocked successfully",
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to block user: {$target->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to block user. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function unblockUser(User $blocker, User $target): array
    {
        // Check if user is blocked
        if (!$blocker->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
            return [
                'success' => false,
                'message' => 'User is not blocked',
                'status' => 400,
            ];
        }

        try {
            $blocker->blockedUsers()->detach($target->id);

            Log::info("User {$blocker->email} unblocked user {$target->email}");

            return [
                'success' => true,
                'message' => "User {$target->name} unblocked successfully",
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to unblock user: {$target->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to unblock user. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function reportUser(User $reporter, User $target, string $message, ?string $reason = null): array
    {
        if ($reporter->id === $target->id) {
            return [
                'success' => false,
                'message' => 'Cannot report yourself',
                'status' => 400,
            ];
        }

        if ($reason && !array_key_exists($reason, self::REPORT_REASONS)) {
            return [
                'success' => false,
                'message' => 'Invalid report reason',
                'status' => 400,
            ];
        }

        try {
            $report = Report::create([
                'reporter_id' => $reporter->id,
                'reported_user_id' => $target->id,
                'message' => $message,
                'reason' => $reason,
            ]);

            if (!$reporter->blockedUsers()->where('reported_user_id', $target->id)->exists()) {
                $reporter->blockedUsers()->attach($target->id);
            }

            $this->notifyAdmin($report);
            $this->sendReportConfirmationEmail($reporter, $report);

            Log::info("User {$reporter->email} reported user {$target->email} for: {$reason}");

            return [
                'success' => true,
                'message' => "User {$target->name} reported and blocked successfully",
                'report' => $report->load(['reporter', 'reportedUser']),
                'admin_notification_sent_to' => self::ADMIN_EMAIL,
                'status' => 201,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to report user: {$target->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to report user. Please try again.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getBlockedUsers(User $user): array
    {
        try {
            $blockedUsers = $user->blockedUsers()->get();

            if ($blockedUsers->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No blocked users found',
                    'data' => [],
                    'count' => 0,
                    'status' => 200,
                ];
            }

            $formattedUsers = $blockedUsers->map(fn($blockedUser) => [
                'id' => $blockedUser->id,
                'name' => $blockedUser->name,
                'username' => $blockedUser->username,
                'avatar' => $blockedUser->avatar_url,
                'email' => $blockedUser->email,
            ])->toArray();

            return [
                'success' => true,
                'message' => 'Blocked users retrieved successfully',
                'data' => $formattedUsers,
                'count' => count($formattedUsers),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get blocked users for user: {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to retrieve blocked users',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Get report reasons
     */
    public function getReportReasons(): array
    {
        try {
            $reasons = collect(self::REPORT_REASONS)->map(function ($label, $key) {
                return [
                    'key' => $key,
                    'label' => $label,
                ];
            })->values()->toArray();

            return [
                'success' => true,
                'message' => 'Report reasons retrieved successfully',
                'data' => $reasons,
                'count' => count($reasons),
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get report reasons - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to retrieve report reasons',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Check if user is blocked by another user
     */
    public function isUserBlocked(User $user, User $potentialBlocker): bool
    {
        return $potentialBlocker->blockedUsers()->where('reported_user_id', $user->id)->exists();
    }

    /**
     * Check if user has blocked another user
     */
    public function hasUserBlocked(User $user, User $targetUser): bool
    {
        return $user->blockedUsers()->where('reported_user_id', $targetUser->id)->exists();
    }

    /**
     * Get report by ID
     */
    public function getReportById(int $reportId): array
    {
        try {
            $report = Report::with(['reporter', 'reportedUser'])->find($reportId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'Report not found',
                    'status' => 404,
                ];
            }

            return [
                'success' => true,
                'message' => 'Report retrieved successfully',
                'data' => $report,
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get report {$reportId} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to retrieve report',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getUserReports(User $user, int $limit = 20): array
    {
        try {
            $reports = Report::where('reporter_id', $user->id)
                ->with(['reportedUser', 'reportedPost'])
                ->latest()
                ->paginate($limit);

            return [
                'success' => true,
                'message' => 'User reports retrieved successfully',
                'data' => $reports->items(),
                'pagination' => [
                    'total' => $reports->total(),
                    'per_page' => $reports->perPage(),
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                ],
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get user reports for {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to retrieve user reports',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getReportsAgainstUser(User $user, int $limit = 20): array
    {
        try {
            $reports = Report::where('reported_user_id', $user->id)
                ->with(['reporter', 'reportedPost'])
                ->latest()
                ->paginate($limit);

            return [
                'success' => true,
                'message' => 'Reports against user retrieved successfully',
                'data' => $reports->items(),
                'pagination' => [
                    'total' => $reports->total(),
                    'per_page' => $reports->perPage(),
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                ],
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get reports against user {$user->email} - {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Failed to retrieve reports against user',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    private function notifyAdmin(Report $report): void
    {
        try {
            Notification::route('mail', self::ADMIN_EMAIL)
                ->notify(new UserReportedNotification($report));

            Log::info("Admin notification sent for report {$report->id}");
        } catch (\Exception $e) {
            Log::warning("Failed to send admin notification for report {$report->id} - {$e->getMessage()}");
        }
    }

    private function sendReportConfirmationEmail(User $reporter, Report $report): void
    {
        try {
            Mail::to($reporter->email)
                ->send(new SupportReportMail($reporter, $report));

            Log::info("Report confirmation email sent to {$reporter->email}");
        } catch (\Exception $e) {
            Log::warning("Failed to send report confirmation email to {$reporter->email} - {$e->getMessage()}");
        }
    }

    public function isValidReason(?string $reason): bool
    {
        if ($reason === null) {
            return true;
        }

        return array_key_exists($reason, self::REPORT_REASONS);
    }

    public function getReasonLabel(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        return self::REPORT_REASONS[$reason] ?? null;
    }
}
