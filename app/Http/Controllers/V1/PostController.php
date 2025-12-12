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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with(['user', 'tags'])
            ->where('status', '!=', 'draft')
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


    public function store(PostStoreRequest $request)
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

        $post = Post::create($validated);

        return response()->json([
            'message' => "Post $post->title created successfully",
            'post' => new PostResource($post)
        ], 201);
    }

//    public function uploadPostImage(Request $request, Post $post)
//    {
//        $this->authorize('update', $post);
//
//        $request->validate([
//            'image_url' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//        ]);
//
//        $image = $request->file('image_url');
//        if (!$image) {
//            return response()->json(['message' => 'No image uploaded'], 422);
//        }
//
//        $extension = $image->getClientOriginalExtension();
//        $slug = Str::slug($post->title ?? Auth::user()->username);
//        $filename = $slug . '-' . time() . '.' . $extension;
//        $path = $image->storeAs('posts-images', $filename, 's3');
//
//        $post->image_url = $path;
//        $post->save();
//
//        return response()->json([
//            'message' => 'Post image uploaded successfully',
//            'data' => new PostResource($post->fresh()),
//        ]);
//    }
//
//    public function uploadPostCover(Request $request)
//    {
//        $request->validate([
//            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//        ]);
//
//        $image = $request->file('cover_image');
//        $extension = $image->getClientOriginalExtension();
//        $slug = Str::slug(Auth::user()->username);
//        $filename = $slug . '-' . time() . '.' . $extension;
//        $path = $image->storeAs('posts-covers', $filename, 's3');
//
//        $user = Auth::user();
//        $user->image_url = $path;
//        $user->save();
//
//        return response()->json([
//            'message' => 'Avatar image uploaded successfully',
//            'data' => new PostResource($user),
//        ]);
//    }

    public function generateCoverImage(GeminiImageService $geminiImage, Request $request)
    {
        $prompt = $request->input('prompt');
        $imageUrl = $geminiImage->generateImage($prompt);

        return response()->json([
            'cover_image' => $imageUrl
        ]);
    }


    public function show(Post $post)
    {
        $this->authorize('view', $post);
        visits($post)->increment();
        $views = visits($post)->count();
        return response()->json([
            'data' => new PostResource($post->load('tags')),
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

    public function destroy(int $id)
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

    public function restore(int $id)
    {
        $post = Post::onlyTrashed()->find($id);

        if (!$post) {
            Log::error("Post $id not found");
            return response()->json(['message' => 'course not found or not trashed.'], 404);
        }
//        $this->authorize('restore', $post);
        if(auth()->id() !== $post->user_id){
            return response()->json(['message' => 'Unauthorized to restore this post.'], 403);
        }
        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully (Unarchived)',
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

    public function drafts(Post $post)
    {
        $user = Auth::user();
        $drafts = $post->where('user_id', $user->id)
            ->where('status', 'draft')->get();
        return response()->json([
            'data' => PostResource::collection($drafts)
        ]);
    }

    public function archivesTrashed(Post $post)
    {
        $this->authorize('view-any', $post);
        $archivedPosts = $post->onlyTrashed()->get();
        return response()->json([
            'archives' => PostResource::collection($archivedPosts)
        ]);
    }

}
