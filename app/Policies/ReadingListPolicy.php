<?php

namespace App\Policies;

use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReadingListPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id;
    }

    public function delete(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id;
    }

    public function restore(User $user, ReadingList $readingList): bool
    {
    }

    public function forceDelete(User $user, ReadingList $readingList): bool
    {
    }
}
