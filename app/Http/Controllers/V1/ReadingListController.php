<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReadingListRequests\ReadingListRequest;
use App\Http\Requests\ReadingListRequests\ReadingListUpdateRequest;
use App\Http\Resources\ReadingListResource;
use App\Models\Post;
use App\Models\ReadingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReadingListController
{
    use AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        $this->authorize('viewAny', ReadingList::class);

        $lists = $user->readingLists()->with('posts.user')->get();

        if ($lists->isEmpty()) {
            return response()->json([
                'message' => 'No reading lists found for this user.',
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

        if (ReadingList::where('title', $request->title)->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'message' => 'A reading list with this title already exists.',
            ], 409);
        }

        $readingList = ReadingList::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        $readingList->loadCount('posts');

        return response()->json([
            'message' => 'Reading list created successfully.',
            'reading_list' => new ReadingListResource($readingList),
        ]);
    }


    public function show(ReadingList $readingList)
    {
        $this->authorize('view', $readingList);

        return response()->json([
            'message' => 'Reading list retrieved successfully.',
            'List' => new ReadingListResource($readingList),
        ]);
    }

    public function Lists(ReadingList $readingList)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        $lists = $user->readingLists()->get();

        return response()->json([
            'message' => 'Reading lists retrieved successfully.',
            'reading_lists' => ReadingListResource::collection($lists),
        ]);
    }

    public function update(ReadingListUpdateRequest $request, ReadingList $readingList)
    {
        $this->authorize('update', $readingList);

        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $readingList->update($data);

        return response()->json([
            'message' => 'Reading list updated successfully.',
            'data' => new ReadingListResource($readingList),
        ]);
    }

    public function destroy(ReadingList $readingList)
    {
        $this->authorize('delete', $readingList);

        $readingList->delete();

        Log::notice('Reading list deleted: ' . $readingList->id . ' by user: ' . auth()->user()->email);
        return response()->json([
            'message' => "Reading list $readingList->title deleted successfully",
        ]);
    }

    public function addPostToReadingList(ReadingList $readingList, Post $post)
    {
        $this->authorize('update', $readingList);

        if ($readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post $post->title is already in the reading list",
            ], 409);
        }

        $readingList->posts()->attach($post->id);

        Log::info("Reading list post $post->title added to reading list: " . $readingList->id);
        return response()->json([
            'message' => "Post $post->title added to reading list ($readingList->title) successfully",
        ]);
    }

    public function removePostFromReadingList(ReadingList $readingList, Post $post)
    {
        $this->authorize('delete', $readingList);

        if (!$readingList->posts()->where('post_id', $post->id)->exists()) {
            return response()->json([
                'message' => "Post $post->title not found in reading list",
            ], 404);
        }

        $readingList->posts()->detach($post->id);

        return response()->json([
            'message' => "Post $post->title removed from reading list ($readingList->title) successfully",
        ]);
    }
}
