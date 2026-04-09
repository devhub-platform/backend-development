<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TopicPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // Allow anyone to view topics
    }

    public function create(User $user): bool
    {
            return $user->role === 'admin'; // Only admins can create topics
    }

    public function update(User $user, Topic $topic): bool
    {
            return $user->role === 'admin'; // Only admins can update topics
    }

    public function delete(User $user, Topic $topic): bool
    {
            return $user->role === 'admin'; // Only admins can delete topics
    }

}
