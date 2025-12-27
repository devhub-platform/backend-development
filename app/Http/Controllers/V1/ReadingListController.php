<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReadingListRequest;
use App\Http\Resources\ReadingListResource;
use App\Models\Post;
use App\Models\ReadingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class ReadingListController
{
    use AuthorizesRequests;

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        $this->authorize('viewAny', ReadingList::class);

        $lists = $user->readingLists()
            ->with('posts.user')
            ->get();

        if ($lists->isEmpty()) {
            return response()->json([
                'message' => 'No reading lists found for this user.',
                'reading_lists' => [],
            ]);
        }

        return response()->json([
            'message' => 'Reading lists retrieved successfully.',
            'reading_lists' => ReadingListResource::collection($lists),
        ]);
    }


    public function store(ReadingListRequest $request)
    {
        $this->authorize('create', ReadingList::class);

        $readingList = ReadingList::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        $readingList->loadCount('posts');

        return (new ReadingListResource($readingList))
            ->response()
            ->setStatusCode(201);
    }


    public function show(ReadingList $readingList)
    {
        $this->authorize('view', $readingList);

        return new ReadingListResource($readingList);
    }

    public function update(ReadingListRequest $request, ReadingList $readingList)
    {
        $this->authorize('update', $readingList);

        $data = $request->validated();

        $readingList->update($data);

        return new ReadingListResource($readingList);
    }

    public function destroy(ReadingList $readingList)
    {
        $this->authorize('delete', $readingList);

        $readingList->delete();

        Log::notice('Reading list deleted: ' . $readingList->id . ' by user: ' . auth()->user()->email);
        return response()->json([
            'message' => 'Reading list deleted successfully',
        ]);
    }

    public function addPostToReadingList(ReadingList $readingList, Post $post)
    {
        $this->authorize('update', $readingList);

        $readingList->posts()->attach($post->id);

        return response()->json([
            'message' => 'Post added to reading list successfully',
        ]);
    }

    public function removePostFromReadingList(ReadingList $readingList, Post $post)
    {
        $this->authorize('delete', $readingList);

        $readingList->posts()->detach($post->id);

        return response()->json([
            'message' => 'Post removed from reading list successfully',
        ]);
    }
}
