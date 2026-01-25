<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\PostsRequests\PostStoreRequest;
use App\Http\Requests\PostsRequests\PostUpdateRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Http\Resources\SearchPostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\Tag;
use App\Services\GeminiImageService;
use App\Services\ModerationService;
use App\Services\ViewedPostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\SummarizePostService;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index() // show all posts except drafts and archived and blocked users
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with(['user', 'tags'])
            ->where('status', '!=', 'draft')
            ->whereNull('deleted_at')
            ->latest()
            ->limit(10)
            ->get();

        return PostResource::collection($posts);
    }

    public function postComments(Post $post)
    {
        $this->authorize('viewAny', Post::class);
        $posts = $post->load('comments');
        return response()->json([
            'data' => PostResource::collection($posts)
        ]);
    }

    public function postsTags()
    {
        $this->authorize('viewAny', Post::class);
        $posts = Post::with('tags')->get();
        return response()->json([
            'data' => PostResource::collection($posts)
        ]);
    }

    public function store(PostStoreRequest $request, ModerationService $moderationService)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            $extension = $image->getClientOriginalExtension();
            $slug = Str::slug(Auth::user()->username);
            $filename = $slug . '-' . time() . '.' . $extension;
            $path = $image->storeAs('posts-covers', $filename, 's3');
            $validated['cover_image'] = strval(Storage::url($path));
        }

        if ($request->hasFile('image_url')) {
            $image = $request->file('image_url');
            $extension = $image->getClientOriginalExtension();
            $slug = Str::slug(Auth::user()->username);
            $filename = $slug . '-' . time() . '.' . $extension;
            $path = $image->storeAs('posts-images', $filename, 's3');
            $validated['image_url'] = strval(Storage::url($path));
        }

        $check_content = $moderationService
            ->moderateContent($validated['content'] . ' ' . $validated['title']);

        if ($check_content['flagged'] ?? false) {
            $moderation = $moderationService->getModerationMessage($check_content);
            Log::warning("Post content flagged by moderation service for user ID: " . auth()->id() . ' ' . $moderation);

            return response()->json([
                'message' => 'Post content violates our content policies and cannot be created , your account may be reviewed , and we take action against it.',
                'reasons' => $moderation
            ], 422);
        }


        $post = Post::create($validated);

        return response()->json([
            'message' => "Post $post->title created successfully",
            'post' => new PostResource($post)
        ], 201);
    }

//    public function generateCoverImage(GeminiImageService $geminiImage, Request $request)
//    {
//        $prompt = $request->input('prompt');
//        $imageUrl = $geminiImage->generateImage($prompt);
//
//        return response()->json([
//            'cover_image' => $imageUrl
//        ]);
//    }


    public function show(Post $post, ViewedPostService $viewedPostService) // view a single post
    {
        $user = auth()->user();
        $this->authorize('view', $post);

        if (!$post->exists()) {
            Log::error("Post {$post->id} not found");
            return response()->json(['message' => 'Post not found.'], 404);
        }

        if ($post->status === 'draft' || $post->trashed()) {
            return response()->json([
                'message' => 'post dose not exist or is not accessible'
            ], 403);
        }

        visits($post)->increment();
        $views = visits($post)->count(); // get total views

        $viewedPostService->trackView($user->id, $post->id);

        return response()->json([
            'data' => new PostResource($post->load('tags')),
            'views' => $views
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post, ModerationService $moderationService)
    {// update a post
        $validated = $request->validated();
        $this->authorize('update', $post);


        $textToModerate = ($validated['content'] ?? '') . ' ' . ($validated['title'] ?? '');
        $check = $moderationService->moderateContent($textToModerate);

        if ($check['flagged'] ?? false) {
            $moderation = $moderationService->getModerationMessage($check);
            Log::warning("Post content flagged by moderation service for user ID: " . auth()->id() . ' ' . $moderation);

            return response()->json([
                'message' => 'Post content violates our content policies and cannot be updated',
                'reasons' => $moderation
            ], 422);
        }

        $post->update($request->validated());

        return response()->json(['message' => "Post $post->title updated successfully",
            'data' => new PostResource($post)
        ], 200);
    }

    public function destroy(int $id) // delete (archive) a post
    {
        $post = Post::find($id);
        $this->authorize('delete', $post);

        if (!$post->exists()) {
            Log::error("Post {$id} not found for deletion");
            return response()->json(['message' => 'Post not found.'], 404);
        }
        $post->delete();

        return response()->json(['message' => "Post $post->title archived successfully"]);
    }

    public function userPosts() // all posts of authenticated user
    {
        $user = auth()->user();
        $posts = $user->posts;

        return response()->json([
            'data' => PostResource::collection($posts)
        ]);
    }

//    public function search(Request $request, Post $post)
//    {
//        $this->authorize('search', $post);
//        $query = $request->input('query');
//        $results = $post->search($query)->take(10)->get();
//        if ($results->isEmpty()) {
//            Log::error('No posts found matching the search criteria: ' . $query);
//            return response()->json(['message' => 'No posts found matching the search criteria.'], 404);
//        }
//        return SearchPostResource::collection($results);
//    }

    public function recentPosts(Post $post)
    {
        $this->authorize('viewAny', $post);
        $posts = $post->latest()->take(5)->get();

        return response()->json([
            'data' => new PostCollection($posts)
        ]);
    }

    public function postsTagsList(Post $post)
    {
        $this->authorize('viewAny', $post);
        $tags = Tag::has('posts')->withCount('posts')->get();

        return response()->json([
            'data' => $tags
        ]);
    }

    public function forceDelete(Post $post)
    {
        $this->authorize('forceDelete', $post);

        $post->forceDelete();
        return response()->json(['message' => "Post $post->title permanently deleted successfully"]);
    }

    public function restore(int $id) # restore (unarchive) a post
    {
        $post = Post::onlyTrashed()->find($id);

        if (!$post) {
            Log::error("Post $id not found");
            return response()->json(['message' => 'course not found or not trashed.'], 404);
        }
//        $this->authorize('restore', $post);
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized to restore this post.'], 403);
        }
        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully (Unarchived)',
            'data' => new PostResource($post),
        ], 200);
    }

    public function drafts(Post $post)
    {
        $user = Auth::user();
        $drafts = $post->where('user_id', $user->id)
            ->where('status', 'draft')->get();
        return response()->json([
            'data' => PostResource::collection($drafts)
        ]);
    }

    public function archivesTrashed() // archived posts
    {
        $this->authorize('viewAny', Post::class);
        $user = auth()->user();
        $archivedPosts = Post::where('user_id', $user->id)->onlyTrashed()->get();
        return response()->json([
            'archives' => PostResource::collection($archivedPosts)
        ]);
    }


//    public function summarizationPost(Post $post, SummarizePostService $summarizeService) // ai summary feature
//    {
//        $content = $post->titlr . ' ' . $post->content;
//        $summary = $summarizeService->summarize($content, 'en');
//
//        if (!$summary) {
//            Log::error("Failed to summarize post: " . $post->id);
//            return response()->json([
//                'message' => 'Failed to summarize the post'
//            ], 503);
//        }
//
//        return response()->json([
//            'message' => 'Post summarized successfully',
//            'post_title' => $post->title,
//            'post by' => $post->user->name,
//            'summary' => $summary
//        ]);
//    }

}
