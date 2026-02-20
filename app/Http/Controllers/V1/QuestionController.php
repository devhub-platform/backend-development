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
use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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
        $perPage = $request->query('per_page', 15);
        $sortBy = $request->query('sort_by', 'recent');
        $isResolved = $request->query('resolved') !== null ? (bool)$request->query('resolved') : null;
        $postId = $request->query('post_id');

        $questions = $this->questionService->getQuestions(
            $perPage,
            $sortBy,
            $isResolved,
            $postId
        );

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ],
        ]);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $this->authorize('create', Question::class);

        $question = $this->questionService->createQuestion(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'data' => new QuestionResource($question),
        ], 201);
    }

    public function show(Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        $question = $this->questionService->getQuestionWithAnswers($question);

        return response()->json([
            'success' => true,
            'data' => new QuestionResource($question->load('answers', 'answers.user')),
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

        return response()->json([
            'success' => true,
            'message' => $vote ? 'Vote recorded' : 'Vote removed',
            'data' => [
                'question_id' => $question->id,
                'vote_score' => $question->fresh()->voteScore(),
                'current_user_vote' => $vote ? $vote->vote_type : null,
            ],
        ]);
    }


    public function userQuestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->query('per_page', 15);

        $questions = $this->questionService->getUserQuestions($user, $perPage);

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ],
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

        $perPage = $request->query('per_page', 15);
        $questions = $this->questionService->searchQuestions($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($questions),
            'meta' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ],
        ]);
    }

    public function trendQuotations()
    {
        $trendingQuestions = $this->questionService->getTrendingQuestions();

        return response()->json([
            'success' => true,
            'data' => QuestionResource::collection($trendingQuestions),
        ]);
    }
}

