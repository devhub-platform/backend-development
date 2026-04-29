<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\User;
use App\Services\QuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileQuestionController extends Controller
{
    public function __construct(private QuestionService $questionService) {}

    /**
     * GET /v1/profile/questions
     *
     * Returns the authenticated user's own questions.
     * Requires auth.
     */
    public function myQuestions(Request $request): JsonResponse
    {
        $perPage   = min($request->integer('per_page', 15), 50);
        $questions = $this->questionService->getUserQuestions($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
        ]);
    }

    /**
     * GET /v1/profile/{userId}/questions
     *
     * Returns any user's public questions.
     * Authenticated users see all questions.
     * Guests see questions from public profiles only.
     */
    public function userQuestions(Request $request, int $userId): JsonResponse
    {
        $targetUser = User::withoutTrashed()->findOrFail($userId);

        // Security: block access to private profiles for guests
        if (!$request->user() && ($targetUser->is_private ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'This profile is private.',
            ], 403);
        }

        // Security: authenticated users respect privacy settings too
        // unless they are the owner or an admin
        if (
            $request->user()
            && ($targetUser->is_private ?? false)
            && $request->user()->id !== $targetUser->id
            && !$request->user()->isAdmin()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This profile is private.',
            ], 403);
        }

        $perPage   = min($request->integer('per_page', 15), 50);
        $questions = $this->questionService->getUserQuestions($targetUser, $perPage);

        return response()->json([
            'success' => true,
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
        ]);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }
}
