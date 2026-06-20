<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\PostsRequests\PostStoreRequest;
use App\Http\Requests\PostsRequests\PostUpdateRequest;
use App\Http\Requests\PostsRequests\ReportPostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Jobs\LogInteractionaiJob;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Report;
use App\Models\Tag;
use App\Notifications\PostReportedNotification;
use App\Services\AI\AddPostToAI;
use App\Services\AI\PostAIImageService;
use App\Services\InteractionLoggerService;
use App\Services\ModerationService;
use App\Services\Posts\HomeFeedService;
use App\Services\Posts\PostCreationService;
use App\Services\TopicPostsService;
use App\Services\UserInterestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;

class PostController
{
    use AuthorizesRequests;

    private InteractionLoggerService $interactionLoggerService;

    public function __construct(
        private PostCreationService $postCreationService,
        private PostAIImageService  $aiImageService,
        private UserInterestService $userInterestService,
        private ModerationService   $moderationService,
        private AddPostToAI         $addPostToAI,
        InteractionLoggerService    $interactionLoggerService,
        private HomeFeedService     $homeFeedService,
        private TopicPostsService   $topicPostsService,
    ) {
        $this->interactionLoggerService = $interactionLoggerService;
    }

    public function topPostsViews(): JsonResponse
    {
        $topPosts = Post::query()
            ->with(['user', 'tags'])
            ->where('status', '!=', 'draft')
            ->when(auth()->check(), fn($query) => $query->whereNotIn('user_id', $this->blockedUserIds()))
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        if ($topPosts->isEmpty()) {
            return response()->json([
                'data'        => [],
                'views_count' => [],
            ]);
        }

        $viewsMap = $topPosts->mapWithKeys(fn($post) => [
            $post->id => Number::abbreviate((int)$post->views),
        ])->toArray();

        return response()->json([
            'data'        => PostResource::collection($topPosts),
            'views_count' => $viewsMap,
        ]);
    }

    public function index(): PostCollection
    {
        $this->authorize('viewAny', Post::class);

        $user    = auth()->user();
        $perPage = (int)request()->input('per_page', 15);
        $page    = (int)request()->input('page', 1);

        // Guest: fast path, no personalisation needed.
        if (!$user) {
            $cacheKey = "home:guest:page:{$page}:pp:{$perPage}";

            $paginator = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($perPage, $page) {
                return $this->homeFeedService->build(
                    null, $perPage, $page,
                    request()->url(), request()->query()
                );
            });

            return new PostCollection($paginator);
        }

        // FIX #2: fetch following IDs exactly once and pass them into
        // HomeFeedService so it does NOT run the same query a second time.
        $followingIds = $user->following()
            ->select('users.id')
            ->pluck('users.id')
            ->all();

        $cacheKey = "home:user:{$user->id}:page:{$page}:pp:{$perPage}";

        if (!empty($followingIds)) {
            $paginator = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $perPage, $page, $followingIds) {
                return $this->homeFeedService->build(
                    $user, $perPage, $page,
                    request()->url(), request()->query(),
                    followingIds: $followingIds,
                );
            });

            return new PostCollection($paginator);
        }

        // No follows — try topic-personalised feed first.
        $blockedIds = $this->blockedUserIds();
        $topicPosts = $this->topicPostsService->forUser($user, $perPage, $page, $blockedIds);

        if ($topicPosts instanceof LengthAwarePaginator) {
            return new PostCollection($topicPosts);
        }

        $paginator = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $perPage, $page, $followingIds) {
            return $this->homeFeedService->build(
                $user, $perPage, $page,
                request()->url(), request()->query(),
                followingIds: $followingIds,
            );
        });

        return new PostCollection($paginator);
    }

    public function postComments(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $post->load('comments.user');

        return response()->json([
            'data' => new PostResource($post),
        ]);
    }

    public function postsTags(): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::with('tags')
            ->where('status', '!=', 'draft')
            ->when(auth()->check(), fn($query) => $query->whereNotIn('user_id', $this->blockedUserIds()))
            ->paginate(15);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function store(PostStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();

        $result = $this->postCreationService->create(
            userId:         auth()->id(),
            authorName:     auth()->user()->name,
            authorPlayerId: auth()->user()->onesignal_player_id ?? '',
            validated:      $validated,
            coverImage:     $request->file('cover_image'),
            images:         $request->file('image_url'),
            requestedTags:  $validated['tags'] ?? [],
        );

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Post content violates our content policies and cannot be created. Your account may be reviewed.',
                'reasons' => $result['reasons'] ?? null,
            ], 422);
        }

        $post = $result['post'];

        return response()->json([
            'message' => "Post '{$post->title}' created successfully",
            'data'    => new PostResource($post->loadMissing(['user', 'tags'])),
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        if ($post->status === 'draft' || $post->trashed()) {
            return response()->json(['message' => 'Post does not exist or is not accessible.'], 404);
        }

        $this->authorize('view', $post);

        $post->load(['tags', 'user']);

        $user               = auth()->user();
        $shouldIncrementView = true;

        if ($user) {
            $postView = PostView::firstOrCreate(
                ['user_id' => $user->id, 'post_id' => $post->id],
                ['viewed_at' => now()]
            );

            $shouldIncrementView = $postView->wasRecentlyCreated;

            LogInteractionaiJob::dispatch(
                (string)$user->id,
                $post->tags->pluck('name')->implode(', ') ?: 'Article',
            );

            if ($shouldIncrementView) {
                $this->userInterestService->trackPostInteraction($user, $post, 'view');
            }
        }

        if ($shouldIncrementView) {
            $post->increment('views');
        }

        return response()->json([
            'data'  => new PostResource($post),
            'views' => Number::abbreviate($post->views),
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post, ModerationService $moderationService): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        $textToModerate  = ($validated['content'] ?? '') . ' ' . ($validated['title'] ?? '');
        $moderationResult = $moderationService->moderateContent($textToModerate);

        if ($moderationResult['flagged'] ?? false) {
            $reasons = $moderationService->getModerationMessage($moderationResult);
            Log::warning('Post content flagged by moderation service for user ID: ' . auth()->id(), ['reasons' => $reasons]);

            return response()->json([
                'message' => 'Post content violates our content policies and cannot be updated.',
                'reasons' => $reasons,
            ], 422);
        }

        $post->update(array_merge($validated, ['is_edit' => true]));

        $this->addPostToAI->updatePostToModel($post->fresh());

        return response()->json([
            'message' => "Post '{$post->title}' updated successfully",
            'data'    => new PostResource($post),
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $title = $post->title;

        if ($post->status === 'draft') {
            $this->addPostToAI->deletePostFromModel($post);
            $post->forceDelete();
            $message = "Post '{$title}' permanently deleted successfully";
        } else {
            $post->delete();
            $message = "Post '{$title}' archived successfully";
        }

        return response()->json(['message' => $message]);
    }

    public function userPosts(): JsonResponse
    {
        $posts = auth()->user()
            ->posts()
            ->with('tags')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function recentPosts(): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::with(['user', 'tags'])
            ->where('status', '!=', 'draft')
            ->when(auth()->check(), fn($query) => $query->whereNotIn('user_id', $this->blockedUserIds()))
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'data' => PostResource::collection($posts),
        ]);
    }

    public function postsTagsList(): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $tags = Tag::has('posts')->withCount('posts')->get();

        return response()->json([
            'data' => $tags,
        ]);
    }

    public function forceDelete(Post $post): JsonResponse
    {
        $this->authorize('forceDelete', $post);

        $title = $post->title;

        $this->addPostToAI->deletePostFromModel($post);
        $post->forceDelete();

        return response()->json([
            'message' => "Post '{$title}' permanently deleted successfully",
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $post = Post::onlyTrashed()->find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found or not archived.',
            ], 404);
        }

        $this->authorize('restore', $post);

        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully (Unarchived)',
            'data'    => new PostResource($post),
        ]);
    }

    public function drafts(): JsonResponse
    {
        $drafts = auth()->user()
            ->posts()
            ->where('status', 'draft')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PostResource::collection($drafts),
            'meta' => [
                'current_page' => $drafts->currentPage(),
                'last_page'    => $drafts->lastPage(),
                'total'        => $drafts->total(),
            ],
        ]);
    }

    public function archivesTrashed(): JsonResponse
    {
        $archivedPosts = auth()->user()
            ->posts()
            ->onlyTrashed()
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PostResource::collection($archivedPosts),
            'meta' => [
                'current_page' => $archivedPosts->currentPage(),
                'last_page'    => $archivedPosts->lastPage(),
                'total'        => $archivedPosts->total(),
            ],
        ]);
    }

    public function reportPost(ReportPostRequest $request, Post $post): JsonResponse
    {
        $user = auth()->user();

        if ($user->id === $post->user_id) {
            return response()->json(['message' => 'You cannot report your own post.'], 400);
        }

        if ($post->trashed()) {
            return response()->json(['message' => 'Post not found or is not accessible.'], 404);
        }

        $existingReport = Report::where('reporter_id', $user->id)
            ->where('reported_post_id', $post->id)
            ->where('type', 'post')
            ->exists();

        if ($existingReport) {
            return response()->json(['message' => 'You have already reported this post.'], 400);
        }

        $validated = $request->validated();

        $report = Report::create([
            'reporter_id'      => $user->id,
            'reported_user_id' => $post->user_id,
            'reported_post_id' => $post->id,
            'type'             => 'post',
            'reason'           => $validated['reason'],
            'message'          => $validated['message'] ?? null,
            'report'           => true,
        ]);

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $this->uploadPostImages($request->file('image_url'));
        }

        $adminEmail = config('services.mail.admin_email_2', 'youssef.ahmed.fci@gmail.com');
        Notification::route('mail', $adminEmail)
            ->notify(new PostReportedNotification($report));

        Log::info("Post reported - Post ID: {$post->id}, Reporter: {$user->email}, Reason: {$validated['reason']}");

        return response()->json([
            'message' => 'Post reported successfully. Our team will review it shortly.',
            'data'    => [
                'report_id' => $report->id,
                'post_id'   => $post->id,
                'reason'    => Report::REASONS[$validated['reason']] ?? $validated['reason'],
            ],
        ], 201);
    }

    public function reasonsToReport(): JsonResponse
    {
        return response()->json([
            'reasons' => Report::REASONS,
        ]);
    }

    /**
     * Blocked + blocker IDs for the authenticated user, cached for 30 s.
     * Shared cache key with HomeFeedService so both read the same entry.
     */
    private function blockedUserIds(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        return Cache::remember(
            "blocked_ids:{$user->id}",
            now()->addSeconds(30),
            fn() => $user->blockedUsers()->pluck('users.id')
                ->merge($user->blockers()->pluck('users.id'))
                ->unique()->values()->all()
        );
    }
}
