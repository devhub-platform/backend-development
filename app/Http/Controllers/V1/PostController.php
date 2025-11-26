<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\PostsRequests\PostStoreRequest;
use App\Http\Requests\PostsRequests\PostUpdateRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Http\Resources\SearchPostResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Post::class);
        $posts = Post::with(['user', 'tags'])->get();
        return response()->json([
            'data' => PostResource::collection($posts)
        ]);
    }

    public function postComments(Post $post)
    {
        $this->authorize('viewAny', Post::class);
        $posts = $post->load('comments');
        return response()->json([
            'data' => $posts
        ]);
    }

    public function postsTags()
    {
        $this->authorize('viewAny', Post::class);
        $posts = Post::with('tags')->get();
        return response()->json([
            'data' => $posts
        ]);
    }

    public function postTags(Post $post)
    {
        $this->authorize('viewAny', Post::class);
        $posts = $post->load('tags');
        return response()->json([
            'data' => $posts
        ]);
    }

    public function store(PostStoreRequest $request)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['slug'] = str($validated['title'])->slug();

        if ($request->hasFile('image_url')) {
            $image = $request->file('image_url');
            $extension = $image->getClientOriginalExtension();
            $filename = str($validated['title'])->slug() . '-' . time() . '.' . $extension;

            $path = $image->storeAs('posts', $filename, 's3');

            $validated['image_url'] = $path;
        }

        $post = Post::create($validated);

        return response()->json([
            'message' => "Post $post->title created successfully",
            'post' => new PostResource($post)
        ], 201);
    }


    public function show(Post $post)
    {
        $this->authorize('view', $post);
        visits($post)->increment();
        $views = visits($post)->count();
        return response()->json([
            'data' => new PostResource($post),
            'views' => $views
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->validated());
        return response()->json(['message' => "Post $post->title updated successfully",
            'data' => new PostResource($post)
        ]);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();
        return response()->json(['message' => "Post $post->title deleted successfully"]);
    }

    public function userPosts(Request $request)
    {
        $this->authorize('userPosts', $post);

        $user = $request->user();
        $posts = Post::where('user_id', $user->id)->get();

        return response()->json([
            'data' => PostResource::collection($posts)
        ]);
    }

    public function search(Request $request, Post $post)
    {
        $this->authorize('search', $post);
        $query = $request->input('query');
        $results = $post->search($query)->take(10)->get();
        if ($results->isEmpty()) {
            Log::error('No posts found matching the search criteria: ' . $query);
            return response()->json(['message' => 'No posts found matching the search criteria.'], 404);
        }
        return SearchPostResource::collection($results);
    }

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

    public function restore(int $id): JsonResource
    {
        $post = Post::onlyTrashed()->findOrFail($id);

        if (!$post) {
            Log::error("Post $id not found");
            return response()->json(['message' => 'course not found or not trashed.'], 404);
        }
        $this->authorize('restore', $post);

        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully',
            'data' => new PostResource($post),
        ], 200);
    }

    public function attachTags(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        $request->validate(['tags' => 'required|array']);
        $tags = collect($request->tags)->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName])->id;
        });
        $post->tags()->syncWithoutDetaching($tags);
        return response()->json($post->load('tags'), 200);
    }

    public function detachTag(Post $post, Tag $tag)
    {
        $post->tags()->detach($tag);
        return response()->json([
            'message' => "Tag {$tag->name} detached from Post {$post->title} successfully",
            $post->load('tags')
        ]);
    }

}
