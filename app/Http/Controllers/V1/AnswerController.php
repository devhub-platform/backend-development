<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AnswersRequests\StoreAnswerRequest;
use App\Http\Requests\AnswersRequests\UpdateAnswerRequest;
use App\Http\Requests\AnswersRequests\VoteAnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Models\Answer;
use App\Models\Question;
use App\Notifications\AnswerAcceptedNotification;
use App\Notifications\NewAnswerNotification;
use App\Services\AnswerService;
use App\Services\QuestionService;
use App\Services\VoteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnswerController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AnswerService   $answerService,
        private VoteService     $voteService,
        private QuestionService $questionService
    ) {}

    public function index(Question $question, Request $request): JsonResponse
    {
        $answers = $this->answerService->getQuestionAnswers(
            $question,
            $request->integer('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'data'    => AnswerResource::collection($answers),
            'meta'    => $this->paginationMeta($answers),
        ]);
    }

    public function store(StoreAnswerRequest $request, Question $question): JsonResponse
    {
        $this->authorize('create', Answer::class);

        $answer = $this->answerService->createAnswer(
            $question,
            $request->user(),
            $request->input('content')
        );

        if ($question->user_id !== $request->user()->id) {
            $question->user->notify(new NewAnswerNotification($answer));
        }

        return response()->json([
            'success' => true,
            'message' => 'Answer created successfully',
            'data'    => new AnswerResource($answer),
        ], 201);
    }

    public function show(Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('view', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AnswerResource($answer->load('user')),
        ]);
    }

    public function update(UpdateAnswerRequest $request, Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('update', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        $answer = $this->answerService->updateAnswer($answer, $request->input('content'));

        return response()->json([
            'success' => true,
            'message' => 'Answer updated successfully',
            'data'    => new AnswerResource($answer),
        ]);
    }

    public function destroy(Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('delete', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        $this->answerService->deleteAnswer($answer);

        return response()->json([
            'success' => true,
            'message' => 'Answer deleted successfully',
        ]);
    }

    /**
     * Accept an answer - question owner can accept multiple answers
     */
    public function accept(Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('accept', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        $this->questionService->acceptAnswer($question, $answer->id);

        $answer->user->notify(new AnswerAcceptedNotification($answer->fresh()));

        return response()->json([
            'success' => true,
            'message' => 'Answer accepted successfully',
            'data'    => new AnswerResource($answer->fresh()),
        ]);
    }

    /**
     * Unaccept a specific answer
     */
    public function unaccept(Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('accept', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        $this->questionService->unacceptAnswer($question, $answer->id);

        return response()->json([
            'success' => true,
            'message' => 'Answer unaccepted successfully',
            'data'    => new AnswerResource($answer->fresh()),
        ]);
    }

    /**
     * Vote on an answer (upvote/downvote - toggleable)
     */
    public function vote(VoteAnswerRequest $request, Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('vote', $answer);

        if ($answer->question_id !== $question->id) {
            return response()->json([
                'success' => false,
                'message' => 'Answer does not belong to this question',
            ], 404);
        }

        $vote = $this->voteService->voteAnswer(
            $answer,
            $request->user(),
            $request->input('vote_type')
        );

        return response()->json([
            'success' => true,
            'message' => $vote ? 'Vote recorded' : 'Vote removed',
            'data'    => [
                'answer_id'         => $answer->id,
                'vote_score'        => $answer->fresh()->voteScore(),
                'current_user_vote' => $vote?->vote_type,
            ],
        ]);
    }

    public function userAnswers(Request $request): JsonResponse
    {
        $answers = $this->answerService->getUserAnswers(
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => AnswerResource::collection($answers),
            'meta'    => $this->paginationMeta($answers),
        ]);
    }

    public function userAcceptedAnswers(Request $request): JsonResponse
    {
        $answers = $this->answerService->getUserAcceptedAnswers(
            $request->user(),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => AnswerResource::collection($answers),
            'meta'    => $this->paginationMeta($answers),
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
