<?php

namespace App\Events;

use App\Models\User;

class UserOnlineEvent extends UserOnline
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
}
