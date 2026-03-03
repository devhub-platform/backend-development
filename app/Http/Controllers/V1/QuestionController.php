<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\QuestionsRequests\StoreQuestionRequest;
use App\Http\Requests\QuestionsRequests\UpdateQuestionRequest;
use App\Http\Requests\QuestionsRequests\VoteQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\QuestionService;
use App\Services\VoteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private QuestionService $questionService,
        private VoteService     $voteService
    ) {}

    /**
     * List questions with filters & sorting
     * Query params: per_page, sort_by (recent|popular|unanswered|hot), resolved, post_id
     */
    public function index(Request $request): JsonResponse
    {
        $questions = $this->questionService->getQuestions(
            perPage:    $request->integer('per_page', 15),
            sortBy:     $request->query('sort_by', 'recent'),
            isResolved: $request->has('resolved') ? $request->boolean('resolved') : null,
            postId:     $request->integer('post_id') ?: null,
        );

        return response()->json([
            'success' => true,
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
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
            'data'    => new QuestionResource($question),
        ], 201);
    }

    public function show(Request $request, Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        $question = $this->questionService->getQuestionWithAnswers($question, $request->user()?->id);

        return response()->json([
            'success' => true,
            'data'    => new QuestionResource($question),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question);

        $question = $this->questionService->updateQuestion($question, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data'    => new QuestionResource($question),
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

    /**
     * Vote on a question (upvote/downvote - toggleable)
     */
    public function vote(VoteQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('vote', $question);

        $vote = $this->voteService->voteQuestion(
            $question,
            $request->user(),
            $request->input('vote_type')
        );

        return response()->json([
            'success'           => true,
            'message'           => $vote ? 'Vote recorded' : 'Vote removed',
            'data'              => [
                'question_id'       => $question->id,
                'vote_score'        => $question->fresh()->voteScore(),
                'current_user_vote' => $vote?->vote_type,
            ],
        ]);
    }

    /**
     * Accept an answer as the best solution
     */
    public function acceptAnswer(Request $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question);

        $request->validate(['answer_id' => 'required|integer|exists:answers,id']);

        $question = $this->questionService->acceptAnswer($question, $request->integer('answer_id'));

        return response()->json([
            'success' => true,
            'message' => 'Answer accepted successfully',
            'data'    => new QuestionResource($question),
        ]);
    }

    /**
     * Unaccept the current accepted answer
     */
    public function unacceptAnswer(Question $question): JsonResponse
    {
        $this->authorize('update', $question);

        $question = $this->questionService->unacceptAnswer($question);

        return response()->json([
            'success' => true,
            'message' => 'Answer unaccepted successfully',
            'data'    => new QuestionResource($question),
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
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
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
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
        ]);
    }

    /**
     * Hot/Trending questions
     */
    public function trending(): JsonResponse
    {
        $questions = $this->questionService->getTrendingQuestions();

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
