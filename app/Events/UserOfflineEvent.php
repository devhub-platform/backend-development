<?php

namespace App\Events;

use App\Models\User;

class UserOfflineEvent extends UserOffline
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
}
