<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    public function creating(User $user): void
    {
        $user->role = $user->role ?? 'user';
        $user->name = Str::title($user->name);
        $user->email = Str::lower($user->email);
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
