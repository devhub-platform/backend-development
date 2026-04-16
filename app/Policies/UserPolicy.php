<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        // Admins cannot update other admins
        if ($model->role === 'admin' && $user->id !== $model->id) {
            return false;
        }

        return $user->id === $model->id || $user->role === 'admin';
    }

    public function delete(User $user, User $model): bool
    {
        // Admins cannot delete other admins
        if ($model->role === 'admin' && $user->id !== $model->id) {
            return false;
        }

        return $user->id === $model->id || $user->role === 'admin';
    }

    public function userPosts(User $user , Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function userComments(User $user , Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function restore(User $user, User $model): bool
    {
        // Admins cannot restore other admins
        if ($model->role === 'admin' && $user->id !== $model->id) {
            return false;
        }

        return $user->role === 'admin';
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Admins cannot permanently delete other admins
        if ($model->role === 'admin' && $user->id !== $model->id) {
            return false;
        }

        return $user->role === 'admin';
    }
}
