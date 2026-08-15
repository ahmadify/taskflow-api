<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->participatesIn($user, $project);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->participatesIn($user, $task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id;
    }

    public function manageAssignments(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id;
    }

    public function manageTags(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id;
    }

    private function participatesIn(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->members()->whereKey($user->id)->exists();
    }
}
