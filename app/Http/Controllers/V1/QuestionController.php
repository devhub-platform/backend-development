<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\QuestionsRequests\StoreQuestionRequest;
use App\Http\Requests\QuestionsRequests\UpdateQuestionRequest;
use App\Http\Requests\QuestionsRequests\VoteQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Notifications\QuestionCreatedNotification;
use App\Services\OneSignalService;
use App\Services\QuestionService;
use App\Services\VoteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class QuestionController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private QuestionService $questionService,
        private VoteService     $voteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $questions = $this->questionService->getQuestions(
            perPage:    $request->integer('per_page', 15),
            sortBy:     $request->query('sort_by', 'recent'),
            isResolved: $request->has('is_resolved') ? $request->boolean('is_resolved') : null,
            postId:     $request->integer('post_id') ?: null,
            tag:        $request->query('tag'),
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

        $user     = $request->user();
        $question = $this->questionService->createQuestion($user, $request->validated());

        // PERF FIX: createQuestion already returns with ['user','tags','images'] loaded
        // No need to load again. Dispatch notifications AFTER response is sent
        // so the HTTP response returns immediately without waiting for follower queries
        // + OneSignal HTTP calls.
        dispatch(function () use ($user, $question) {
            $followers = $user->followers()
                ->where('users.id', '!=', $user->id)
                ->get()
                ->filter(fn($follower) => $follower->isNotificationEnabled('new_post_from_following'));

            if ($followers->isEmpty()) return;

            Notification::send(
                $followers,
                new QuestionCreatedNotification($question)
            );

            $playerIds = $followers->pluck('onesignal_player_id')->filter()->values()->all();

            if (!empty($playerIds)) {
                app(OneSignalService::class)->sendToUsers(
                    message:   Str::limit($question->content, 100),
                    playerIds: $playerIds,
                    heading:   "{$user->name} posted a new question",
                    url:       'deeplink://questions/' . $question->id,
                    data:      [
                        'question_id' => $question->id,
                        'author_id'   => $user->id,
                    ]
                );
            }
        })->afterResponse();

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

    public function vote(VoteQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('vote', $question);

        $vote  = $this->voteService->voteQuestion($question, $request->user(), $request->input('vote_type'));
        $score = $this->voteService->getQuestionVoteScore($question);

        return response()->json([
            'success' => true,
            'message' => $vote ? 'Vote recorded' : 'Vote removed',
            'data'    => [
                'question_id'       => $question->id,
                'vote_score'        => $score['score'],
                'upvotes'           => $score['upvotes'],
                'downvotes'         => $score['downvotes'],
                'current_user_vote' => $vote?->vote_type,
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

    public function trending(): JsonResponse
    {
        $questions = $this->questionService->getTrendingQuestions(limit: 5);

        return response()->json([
            'success' => true,
            'data'    => QuestionResource::collection($questions),
        ]);
    }

    /**
     * Filter questions by tag.
     * Route must be registered BEFORE /{question} wildcard in api.php:
     *   Route::get('questions/by-tag/{tag}', 'byTag');   ← before
     *   Route::get('questions/{question}',   'show');    ← after
     */
    public function byTag(Request $request, string $tag): JsonResponse
    {
        $questions = $this->questionService->getQuestions(
            perPage: $request->integer('per_page', 15),
            sortBy:  $request->query('sort_by', 'recent'),
            tag:     $tag,
        );

        return response()->json([
            'success' => true,
            'tag'     => $tag,
            'data'    => QuestionResource::collection($questions),
            'meta'    => $this->paginationMeta($questions),
        ]);
    }

    /**
     * Returns shareable URL and metadata for a question.
     * Route must be registered BEFORE /{question} wildcard:
     *   Route::get('questions/{question}/share', 'share');  ← before generic /{question}
     */
    public function share(Question $question): JsonResponse
    {
        $question->loadMissing('tags');

        return response()->json([
            'success' => true,
            'data'    => [
                'url'        => config('app.frontend_url') . '/questions/' . $question->id,
                'slug_url'   => config('app.frontend_url') . '/questions/' . $question->slug,
                'title'      => $question->title,
                'short_text' => Str::limit(strip_tags($question->content), 120),
                'tags'       => $question->tags->pluck('name'),
            ],
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
