<?php

namespace App\Http\Controllers\V1;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TagFollowController extends Controller
{
    public function follow(int $tagId): JsonResponse
    {
        $tag = Tag::findOrFail($tagId);
        $user = Auth::user();

        // Only attach if not already following
        if (!$user->isFollowingTag($tagId)) {
            $user->followTag($tagId);
        }

        return response()->json([
            'message' => 'Successfully followed tag',
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
            ],
        ], 200);
    }

    public function unfollow(int $tagId): JsonResponse
    {
        $tag = Tag::findOrFail($tagId);
        $user = Auth::user();

        $user->unfollowTag($tagId);

        return response()->json([
            'message' => 'Successfully unfollowed tag',
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
            ],
        ], 200);
    }

    public function listFollowing(): JsonResponse
    {
        $user = Auth::user();

        $tags = $user->followedTags()
            ->select('id', 'name')
            ->get();

        return response()->json([
            'message' => 'Tags retrieved successfully',
            'count' => $tags->count(),
            'tags' => $tags,
        ], 200);
    }
}
