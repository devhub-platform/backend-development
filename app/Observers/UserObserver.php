<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
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
        Cache::forget("user_profile_{$user->id}");
    }

    public function deleted(User $user): void
    {
        Cache::forget("user_profile_{$user->id}");
    }

    public function restored(User $user): void
    {
    }
}
