<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id == $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id == $user->id;
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $comment->user_id == $user->id;
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $comment->user_id == $user->id;
    }

    public function pin(User $user, Comment $comment): bool
    {
        return $comment->post->user_id == $user->id;
    }

    public function unpin(User $user, Comment $comment): bool
    {
        return $comment->post->user_id == $user->id;
    }
}
