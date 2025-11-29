<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        $user->role = $user->role ?? 'user';
    }

    public function updated(User $user): void
    {
    }

    public function deleted(User $user): void
    {
    }

    public function restored(User $user): void
    {
    }
}
