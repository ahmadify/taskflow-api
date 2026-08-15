<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user, Task $task): bool
    {
        return $this->participatesIn($user, $task->project);
    }

    public function create(User $user, Task $task): bool
    {
        return $this->participatesIn($user, $task->project);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            && $this->participatesIn($user, $comment->task->project);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->task->project->owner_id === $user->id) {
            return true;
        }

        return $comment->user_id === $user->id
            && $this->participatesIn($user, $comment->task->project);
    }

    private function participatesIn(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->members()->whereKey($user->id)->exists();
    }
}
