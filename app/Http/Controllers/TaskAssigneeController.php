<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAssigneeRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class TaskAssigneeController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreTaskAssigneeRequest $request, Project $project, Task $task): JsonResponse
    {
        $task->assignees()->attach($request->validated('user_id'), ['assigned_at' => now()]);

        return response()->json([
            'message' => 'Task assignee added successfully.',
            'data' => [
                'task' => new TaskResource(
                    $task->load(['creator', 'assignees', 'tags'])->loadCount('comments'),
                ),
            ],
        ], 201);
    }

    public function destroy(Project $project, Task $task, User $user): JsonResponse
    {
        $this->authorize('manageAssignments', $task);
        $task->assignees()->detach($user->id);

        return response()->json([
            'message' => 'Task assignee removed successfully.',
            'data' => null,
        ]);
    }
}
