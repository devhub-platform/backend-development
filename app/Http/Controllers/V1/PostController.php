<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\PostsRequests\PostStoreRequest;
use App\Http\Requests\PostsRequests\PostUpdateRequest;
use App\Http\Requests\PostsRequests\ReportPostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Http\Resources\TrendingPostResource;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Report;
use App\Models\Tag;
use App\Notifications\PostReportedNotification;
use App\Services\AI\AddPostToAI;
use App\Services\AI\PostAIImageService;
use App\Services\Posts\PostCreationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;

class PostController
{
    use AuthorizesRequests;

    public function __construct(
        private PostCreationService $postCreationService,
        private PostAIImageService $aiImageService,
    )
    {
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
                'data' => [],
                'views_count' => [],
            ]);
        }

        $viewsMap = $topPosts->mapWithKeys(fn($post) => [
            $post->id => Number::abbreviate((int)$post->views)
        ])->toArray();

        return response()->json([
            'data' => PostResource::collection($topPosts),
            'views_count' => $viewsMap,
        ]);
    }

    public function index(): PostCollection
    {
        $this->authorize('viewAny', Post::class);

        $user = auth()->user();

        $posts = Post::query()
            ->with(['user', 'tags'])
            ->where('status', '!=', 'draft')
            ->when(auth()->check(), fn($query) => $query->whereNotIn('user_id', $this->blockedUserIds()))
            ->prioritizeFollowedTags($user)
            ->latest()
            ->paginate(10);

        return new PostCollection($posts);
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
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(PostStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();

        $result = $this->postCreationService->create(
            userId: auth()->id(),
            authorName: auth()->user()->name,
            authorPlayerId: auth()->user()->onesignal_player_id,
            validated: $validated,
            coverImage: $request->file('cover_image'),
            images: $request->file('image_url'),
            requestedTags: $validated['tags'] ?? [],
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
            'data' => new PostResource($post->loadMissing(['user', 'tags']))
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        if ($post->status === 'draft' || $post->trashed()) {
            return response()->json([
                'message' => 'Post does not exist or is not accessible.'
            ], 404);
        }

        $this->authorize('view', $post);

        $post->load(['tags', 'user']);

        $user = auth()->user();
        $shouldIncrementView = false;

        if ($user) {
            $postView = PostView::firstOrCreate(
                ['user_id' => $user->id, 'post_id' => $post->id],
                ['viewed_at' => now()]
            );

            $shouldIncrementView = $postView->wasRecentlyCreated;
        } else {
            $shouldIncrementView = true;
        }

        if ($shouldIncrementView) {
            $post->increment('views');
        }

        return response()->json([
            'data' => new PostResource($post),
            'views' => Number::abbreviate((int)$post->fresh()->views),
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post, ModerationService $moderationService): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        $textToModerate = ($validated['content'] ?? '') . ' ' . ($validated['title'] ?? '');
        $moderationResult = $moderationService->moderateContent($textToModerate);

        if ($moderationResult['flagged'] ?? false) {
            $reasons = $moderationService->getModerationMessage($moderationResult);
            Log::warning("Post content flagged by moderation service for user ID: " . auth()->id(), ['reasons' => $reasons]);

            return response()->json([
                'message' => 'Post content violates our content policies and cannot be updated.',
                'reasons' => $reasons
            ], 422);
        }

        $post->update(array_merge($validated, ['is_edit' => true]));

        return response()->json([
            'message' => "Post '{$post->title}' updated successfully",
            'data' => new PostResource($post)
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $title = $post->title;

        if ($post->status === 'draft') {
            $post->forceDelete();
            $message = "Post '{$title}' permanently deleted successfully";
        } else {
            $post->delete();
            $message = "Post '{$title}' archived successfully";
        }

        return response()->json([
            'message' => $message
        ]);
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
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
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
            'data' => $tags
        ]);
    }

    public function forceDelete(Post $post): JsonResponse
    {
        $this->authorize('forceDelete', $post);

        $title = $post->title;
        $post->forceDelete();

        return response()->json([
            'message' => "Post '{$title}' permanently deleted successfully"
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $post = Post::onlyTrashed()->find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found or not archived.'
            ], 404);
        }

        $this->authorize('restore', $post);

        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully (Unarchived)',
            'data' => new PostResource($post),
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
                'last_page' => $drafts->lastPage(),
                'total' => $drafts->total(),
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
                'last_page' => $archivedPosts->lastPage(),
                'total' => $archivedPosts->total(),
            ],
        ]);
    }

    public function reportPost(ReportPostRequest $request, Post $post): JsonResponse
    {
        $user = auth()->user();

        if ($user->id === $post->user_id) {
            return response()->json([
                'message' => 'You cannot report your own post.',
            ], 400);
        }

        if ($post->trashed()) {
            return response()->json([
                'message' => 'Post not found or is not accessible.',
            ], 404);
        }

        $existingReport = Report::where('reporter_id', $user->id)
            ->where('reported_post_id', $post->id)
            ->where('type', 'post')
            ->exists();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this post.',
            ], 400);
        }

        $validated = $request->validated();

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $post->user_id,
            'reported_post_id' => $post->id,
            'type' => 'post',
            'reason' => $validated['reason'],
            'message' => $validated['message'] ?? null,
            'report' => true,
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
            'data' => [
                'report_id' => $report->id,
                'post_id' => $post->id,
                'reason' => Report::REASONS[$validated['reason']] ?? $validated['reason'],
            ],
        ], 201);
    }

    public function reasonsToReport(): JsonResponse
    {
        return response()->json([
            'reasons' => Report::REASONS,
        ]);
    }

    private function blockedUserIds(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $blocked = $user->blockedUsers()->pluck('users.id');
        $blockers = $user->blockers()->pluck('users.id');

        return $blocked->merge($blockers)->unique()->values()->all();
    }

}
