<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TagFollowController extends Controller
{
    public function follow(Request $request, int $tagId)
    {
        $user = Auth::user();
        $tag = Tag::find($tagId);

        if (!$tag) {
            return response()->json(['error' => 'Tag not found'], 404);
        }

        $user->followTag($tag->id);

        return response()->json(['message' => 'Followed tag', 'tag' => $tag->name], 200);
    }

    public function unfollow(Request $request, int $tagId)
    {
        $user = Auth::user();
        $tag = Tag::find($tagId);

        if (!$tag) {
            return response()->json(['error' => 'Tag not found'], 404);
        }

        $user->unfollowTag($tag->id);

        return response()->json(['message' => 'Unfollowed tag', 'tag' => $tag->name], 200);
    }

    public function listFollowing()
    {
        $user = Auth::user();

        $tags = $user->followedTags()
            ->select('tags.id as id', 'tags.name')
            ->pluck('tags.id', 'tags.name');

        if ($tags->isEmpty()) {
            Log::error('User ' . $user->name . ' has no followed tags');
            return response()->json(['message' => 'No followed tags found'], 404);
        }

        return response()->json(['tags' => $tags], 200);
    }
}
