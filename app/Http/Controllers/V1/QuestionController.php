<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\QuestionsRequests\StoreQuestionRequest;
use App\Http\Requests\QuestionsRequests\UpdateQuestionRequest;
use App\Http\Requests\QuestionsRequests\VoteQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Notifications\QuestionCreatedNotification;
use App\Services\QuestionService;
use App\Services\VoteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use OneSignal;
use App\Services\OneSignalService;

class QuestionController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private QuestionService $questionService,
        private VoteService     $voteService
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $questions = $this->questionService->getQuestions(
            perPage: $request->integer('per_page', 15),
            sortBy: $request->query('sort_by', 'recent'),
            isResolved: $request->has('is_resolved') ? $request->boolean('is_resolved') : null,
            postId: $request->integer('post_id') ?: null,
            tag: $request->query('tag'),
        );

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => $this->paginationMeta($questions),
        ]);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $this->authorize('create', Question::class);

        $user = $request->user();

        // Create question (service already loads tags + images)
        $question = $this->questionService->createQuestion(
            $user,
            $request->validated()
        );

        /**
         * Reload full relations safely before any usage
         * This guarantees Resource always gets complete data
         */
        $question->load(['user', 'tags', 'images']);

        // Notifications (safe user-only load)
        $followers = $user->followers
            ->where('id', '!=', $user->id)
            ->filter(fn($follower) => $follower->isNotificationEnabled('new_post_from_following')
            );

        if ($followers->isNotEmpty()) {
            Notification::send(
                $followers,
                new QuestionCreatedNotification($question->load('user'))
            );

            // OneSignal push notifications to all followers
            $playerIds = $followers->pluck('onesignal_player_id')->filter()->values()->all();

            if (!empty($playerIds)) {
                $service = app(OneSignalService::class);
                $service->sendToUsers(
                    message: Str::limit($question->content, 100),
                    playerIds: $playerIds,
                    heading: "{$user->name} posted a new question",
                    url: 'deeplink://questions/' . $question->id,
                    data: [
                        'question_id' => $question->id,
                        'author_id' => $user->id,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'data' => new QuestionResource($question),
        ], 201);
    }

    public function show(Request $request, Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        $question = $this->questionService->getQuestionWithAnswers($question, $request->user()?->id);

        return response()->json([
            'success' => true,
            'data' => new QuestionResource($question),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question);

        $question = $this->questionService->updateQuestion($question, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data' => new QuestionResource($question),
        ]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $this->authorize('delete', $question);

        $this->questionService->deleteQuestion($question);

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully',
        ]);
    }

    public function vote(VoteQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('vote', $question);

        $vote = $this->voteService->voteQuestion(
            $question,
            $request->user(),
            $request->input('vote_type')
        );

        // Reload votes from DB after voting - single query
        $question->load('votes');
        $upvotes = $question->votes->where('vote_type', 'upvote')->count();
        $downvotes = $question->votes->where('vote_type', 'downvote')->count();

        return response()->json([
            'success' => true,
            'message' => $vote ? 'Vote recorded' : 'Vote removed',
            'data' => [
                'question_id' => $question->id,
                'vote_score' => $upvotes - $downvotes,
                'current_user_vote' => $vote?->vote_type,
            ],
        ]);
    }

    public function userQuestions(Request $request): JsonResponse
    {
        $questions = $this->questionService->getUserQuestions(
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => $this->paginationMeta($questions),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        if (strlen($query) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 3 characters',
            ], 400);
        }

        $questions = $this->questionService->searchQuestions(
            $query,
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => $this->paginationMeta($questions),
        ]);
    }

    public function trending(): JsonResponse
    {
        $questions = $this->questionService->getTrendingQuestions(limit: 5);

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
        ]);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
