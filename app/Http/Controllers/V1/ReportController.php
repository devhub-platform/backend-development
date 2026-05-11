<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ReportController
{
    use AuthorizesRequests;

    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function block(User $target): JsonResponse
    {
        $blocker = Auth::user();

        if (!$blocker) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->reportService->blockUser($blocker, $target);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function report(Request $request, User $target): JsonResponse
    {
        $reporter = Auth::user();

        if (!$reporter) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|in:spam,harassment,hate_speech,violence,adult_content,copyright,misinformation,other',
        ]);

        $result = $this->reportService->reportUser(
            $reporter,
            $target,
            $validated['message'] ?? null,
            $validated['reason'] ?? null
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new ReportResource($result['report']),
            'admin_notification_sent_to' => $result['admin_notification_sent_to'],
        ], $result['status']);
    }

    public function unblock(User $target): JsonResponse
    {
        $blocker = Auth::user();

        if (!$blocker) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->reportService->unblockUser($blocker, $target);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function blockList(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

//        $cacheKey = "blocked_users_user_{$user->id}";
//        $cachedResult = Cache::get($cacheKey);

//        if ($cachedResult) {
//            return response()->json([
//                'message' => $cachedResult['message'],
//                'count' => $cachedResult['count'],
//                'data' => $cachedResult['data'],
//            ], $cachedResult['status']);
//        }

        $result = $this->reportService->getBlockedUsers($user);

//        if ($result['success']) {
//            Cache::put($cacheKey, $result, 60 * 60); // 1 hour
//        }

        return response()->json([
            'message' => $result['message'],
            'count' => $result['count'],
            'data' => $result['data'],
        ], 200);
    }

    public function reportList(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

//        $cacheKey = "user_reports_user_{$user->id}";
//        $cachedResult = Cache::get($cacheKey);

//        if ($cachedResult) {
//            return response()->json(array_merge($cachedResult, ['cached' => true]), $cachedResult['status']);
//        }

        $result = $this->reportService->getUserReports($user);

//        if ($result['success']) {
//            Cache::put($cacheKey, $result, 60 * 60); // 1 hour
//        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 200);
    }

    public function reason(): JsonResponse
    {
        $result = $this->reportService->getReportReasons();

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
            'count' => $result['count'],
        ], $result['status']);
    }
}
