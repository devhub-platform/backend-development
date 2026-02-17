<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReadingListRequests\AddNoteToPostRequest;
use App\Http\Requests\ReadingListRequests\ReadingListRequest;
use App\Http\Requests\ReadingListRequests\ReadingListUpdateRequest;
use App\Http\Resources\ReadingListResource;
use App\Models\Post;
use App\Models\ReadingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReadingListController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $user = Auth::user();

        $this->authorize('viewAny', ReadingList::class);

        $lists = $user->readingLists()
            ->with('posts.user')
            ->withCount('posts')
            ->paginate(10);

        return response()->json([
            'message' => 'Reading lists retrieved successfully.',
            'count' => $lists->total(),
            'data' => ReadingListResource::collection($lists),
        ]);
    }

    public function store(ReadingListRequest $request): JsonResponse
    {
        $this->authorize('create', ReadingList::class);

        $validated = $request->validated();
        $validated['user_id'] = Auth::id();

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
        ]);
    }

    public function update(ReadingListUpdateRequest $request, ReadingList $readingList): JsonResponse
    {
        $this->authorize('update', $readingList);

        $readingList->update($request->validated());
        $readingList->loadCount('posts');

        return response()->json([
            'message' => 'Reading list updated successfully.',
            'data' => new ReadingListResource($readingList),
        ]);
    }

    public function destroy(ReadingList $readingList): JsonResponse
    {
        $this->authorize('delete', $readingList);

        $title = $readingList->title;
        $readingList->delete();

        return response()->json([
            'message' => "Reading list '{$title}' deleted successfully.",
        ]);
    }

    public function addPostToReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if ($this->postExistsInList($readingList, $post)) {
            return response()->json([
                'message' => 'Post is already in this reading list.',
            ], Response::HTTP_CONFLICT);
        }

        if ($post->status === 'draft') {
            return response()->json([
                'message' => 'Post does not exist.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($post->user && Auth::user()->isBlockedWith($post->user)) {
            return response()->json([
                'message' => 'You cannot add this post to your reading list.',
            ], 403);
        }

        $readingList->posts()->attach($post->id);

        return response()->json([
            'message' => 'Post added to reading list successfully.',
            'data' => new ReadingListResource($readingList->fresh()),
        ], 201);
    }

    public function removePostFromReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$this->postExistsInList($readingList, $post)) {
            return response()->json([
                'message' => 'Post not found in this reading list.',
            ], Response::HTTP_NOT_FOUND);
        }

        $readingList->posts()->detach($post->id);

        return response()->json([
            'message' => 'Post removed from reading list successfully.',
            'data' => new ReadingListResource($readingList->fresh()),
        ]);
    }

    public function addNoteToPostInReadingList(AddNoteToPostRequest $request, ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$this->postExistsInList($readingList, $post)) {
            return response()->json([
                'message' => 'Post not found in this reading list.',
            ], Response::HTTP_NOT_FOUND);
        }

        $readingList->posts()->updateExistingPivot($post->id, [
            'note' => $request->validated('note'),
        ]);

        return response()->json([
            'message' => 'Note added to post successfully.',
            'data' => [
                'note' => $request->validated('note'),
            ],
        ]);
    }

    public function deleteNoteInPostInReadingList(ReadingList $readingList, Post $post): JsonResponse
    {
        $this->authorize('update', $readingList);

        if (!$this->postExistsInList($readingList, $post)) {
            return response()->json([
                'message' => 'Post not found in this reading list.',
            ], Response::HTTP_NOT_FOUND);
        }

        $readingList->posts()->updateExistingPivot($post->id, ['note' => null]);

        return response()->json([
            'message' => 'Note deleted successfully.',
        ]);
    }

    public function duplicateReadingList(ReadingList $readingList): JsonResponse
    {
        $this->authorize('create', ReadingList::class);

        $newReadingList = $readingList->replicate();
        $newReadingList->title = $readingList->title . ' (Copy)';
        $newReadingList->user_id = Auth::id();
        $newReadingList->save();

        foreach ($readingList->posts as $post) {
            $newReadingList->posts()->attach($post->id, ['note' => $post->pivot->note]);
        }

        $newReadingList->loadCount('posts');

        return response()->json([
            'message' => 'Reading list duplicated successfully.',
            'data' => new ReadingListResource($newReadingList),
        ], 201);
    }

    private function postExistsInList(ReadingList $readingList, Post $post): bool
    {
        return $readingList->posts()->where('post_id', $post->id)->exists();
    }
}
