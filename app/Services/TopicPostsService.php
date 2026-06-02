<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class TopicPostsService
{
    public function forUser(User $user, int $perPage, int $page, array $blockedIds): ?LengthAwarePaginator
    {
        if (!$user->onboarding_completed_at && $user->topics()->exists()) {
            $userTopicNames = $user->topics()->pluck('topics.name')->all();

            $matchingTagQuery = Tag::query();

            if (!empty($userTopicNames)) {
                $matchingTagQuery->whereIn('name', $userTopicNames);
                $matchingTagQuery->orWhere(function ($q) use ($userTopicNames) {
                    foreach ($userTopicNames as $name) {
                        $q->orWhere('name', 'like', "%{$name}%");
                    }
                });
            }

            $matchingTagIds = $matchingTagQuery->distinct()->pluck('id')->all();

            $tagSelect = ['tags.id', 'tags.name', 'tags.created_at'];
            if (Schema::hasColumn('tags', 'embedding')) {
                $tagSelect[] = 'tags.embedding';
            }

            $postQuery = Post::query()
                ->select('id', 'user_id', 'title', 'content', 'slug', 'created_at', 'updated_at', 'views')
                ->with([
                    'user:id,name,username,avatar_url',
                    'tags' => fn($q) => $q->select($tagSelect),
                ])
                ->where('status', '!=', 'draft')
                ->whereNotIn('user_id', $blockedIds)
                ->orderByDesc('created_at');

            if (!empty($matchingTagIds)) {
                $postQuery->whereHas('tags', function ($q) use ($matchingTagIds) {
                    $q->whereIn('tags.id', $matchingTagIds);
                });
            } else {
                $postQuery->whereHas('tags', function ($q) use ($userTopicNames) {
                    $q->whereIn('tags.name', $userTopicNames);
                });
            }

            return $postQuery->paginate($perPage, ['*'], 'page', $page);
        }

        return null;
    }

}
