<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function report(ReportRequest $request, User $target): JsonResponse
    {
        $reporter = Auth::user();

        if (!$reporter) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validated();

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

    public function reportedUsers(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->reportService->getReportedUsers($user);

        return response()->json([
//            'success' => $result['success'],
            'message' => $result['message'],
            'count' => $result['count'],
            'data' => $result['data'],
//            'status' => $result['status'],
        ], 200);
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

//        $this->authorize('view-block-list', Report::class);

        $result = $this->reportService->getBlockedUsers($user);

        return response()->json([
            'message' => $result['message'],
            'count' => $result['count'],
            'data' => $result['data'],
        ], $result['status']);
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
