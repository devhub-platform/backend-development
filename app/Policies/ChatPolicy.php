<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Musonza\Chat\Models\Conversation;

class ChatPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('messageable_id', $user->id)
            ->where('messageable_type', User::class)
            ->exists();
    }
}
