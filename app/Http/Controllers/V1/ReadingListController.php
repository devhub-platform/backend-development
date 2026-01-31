<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReadingListRequests\ReadingListRequest;
use App\Http\Requests\ReadingListRequests\ReadingListUpdateRequest;
use App\Http\Resources\ReadingListResource;
use App\Models\Post;
use App\Models\ReadingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ReadingListController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        $this->authorize('viewAny', ReadingList::class);

        $lists = $user->readingLists()->with('posts.user')->paginate(10);

        return response()->json([
            'message' => 'Reading lists retrieved successfully.',
            'count' => $lists->total(),
            'data' => ReadingListResource::collection($lists),
        ], 200);
    }

    public function store(ReadingListRequest $request): JsonResponse
    {
        $this->authorize('create', ReadingList::class);

        $validated = $request->validated();

        if (ReadingList::where('title', $validated['title'])->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'message' => 'A reading list with this title already exists.',
            ], 409);
        }

        $validated['user_id'] = auth()->id();
        $readingList = ReadingList::create($validated);
        $readingList->loadCount('posts');

        return response()->json([
            'message' => 'Reading list created successfully.',
            'data' => new ReadingListResource($readingList),
        ], 201);
    }

    public function show(ReadingList $readingList): JsonResponse
    {
        $this->authorize('view', $readingList);
        $readingList->loadCount('posts');

        return response()->json([
            'message' => 'Reading list retrieved successfully.',
            'data' => new ReadingListResource($readingList),
        ], 200);
    }

    public function update(ReadingListUpdateRequest $request, ReadingList $readingList): JsonResponse
    {
        $this->authorize('update', $readingList);

        $data = $request->validated();
        $readingList->update($data);

        return response()->json([
            'message' => 'Reading list updated successfully.',
            'data' => new ReadingListResource($readingList),
        ], 200);
    }

    public function destroy(ReadingList $readingList): JsonResponse
    {
        $this->authorize('delete', $readingList);

        $title = $readingList->title;
        $readingList->delete();

        return response()->json([
            'message' => "Reading list '$title' deleted successfully.",
        ], 200);
    }

    public function addPostToReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        // Check if post already exists in reading list
        if ($readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post is already in this reading list.",
            ], 409);
        }

        $readingList->posts()->attach($post->id);

        return response()->json([
            'message' => "Post added to reading list successfully.",
            'data' => new ReadingListResource($readingList->fresh()),
        ], 201);
    }

    public function removePostFromReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post not found in this reading list.",
            ], 404);
        }

        $readingList->posts()->detach($post->id);

        return response()->json([
            'message' => "Post removed from reading list successfully.",
            'data' => new ReadingListResource($readingList->fresh()),
        ], 200);
    }

    public function addNoteToPostInReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post not found in this reading list.",
            ], 404);
        }

        $validated = request()->validate([
            'note' => 'required|string|max:1000',
        ]);

        $readingList->posts()->updateExistingPivot($post->id, ['note' => $validated['note']]);

        return response()->json([
            'message' => "Note added to post successfully.",
            'data' => [
                'note' => $validated['note'],
            ],
        ], 200);
    }

    public function deleteNoteInPostInReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post not found in this reading list.",
            ], 404);
        }

        $readingList->posts()->updateExistingPivot($post->id, ['note' => null]);

        return response()->json([
            'message' => "Note deleted successfully.",
        ], 200);
    }

    public function duplicateReadingList(ReadingList $readingList): JsonResponse
    {
        $this->authorize('create', ReadingList::class);

        $newReadingList = $readingList->replicate();
        $newReadingList->title = $readingList->title . ' (Copy)';
        $newReadingList->save();

        foreach ($readingList->posts as $post) {
            $newReadingList->posts()->attach($post->id, ['note' => $post->pivot->note]);
        }

        return response()->json([
            'message' => 'Reading list duplicated successfully.',
            'data' => new ReadingListResource($newReadingList),
        ], 201);
    }
}
