<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }
}
