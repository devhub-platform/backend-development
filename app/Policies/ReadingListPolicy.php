<?php

namespace App\Policies;

use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReadingListPolicy{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        //
    }

    public function view(User $user, ReadingList $readingList): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, ReadingList $readingList): bool
    {
    }

    public function delete(User $user, ReadingList $readingList): bool
    {
    }

    public function restore(User $user, ReadingList $readingList): bool
    {
    }

    public function forceDelete(User $user, ReadingList $readingList): bool
    {
    }
}
