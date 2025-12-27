<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingListRequest;
use App\Http\Resources\ReadingListResource;
use App\Models\ReadingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReadingListController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ReadingList::class);

        return ReadingListResource::collection(ReadingList::all());
    }

    public function store(ReadingListRequest $request)
    {
        $this->authorize('create', ReadingList::class);

        return new ReadingListResource(ReadingList::create($request->validated()));
    }

    public function show(ReadingList $readingList)
    {
        $this->authorize('view', $readingList);

        return new ReadingListResource($readingList);
    }

    public function update(ReadingListRequest $request, ReadingList $readingList)
    {
        $this->authorize('update', $readingList);

        $readingList->update($request->validated());

        return new ReadingListResource($readingList);
    }

    public function destroy(ReadingList $readingList)
    {
        $this->authorize('delete', $readingList);

        $readingList->delete();

        return response()->json();
    }
}
