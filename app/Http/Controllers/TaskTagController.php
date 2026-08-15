<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachTaskTagRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class TaskTagController extends Controller
{
    use AuthorizesRequests;

    public function store(AttachTaskTagRequest $request, Project $project, Task $task): JsonResponse
    {
        $task->tags()->attach($request->validated('tag_id'));

        return response()->json([
            'message' => 'Task tag attached successfully.',
            'data' => [
                'task' => new TaskResource(
                    $task->load(['creator', 'assignees', 'tags'])->loadCount('comments'),
                ),
            ],
        ], 201);
    }

    public function destroy(Project $project, Task $task, Tag $tag): JsonResponse
    {
        $this->authorize('manageTags', $task);
        $task->tags()->detach($tag->id);

        return response()->json([
            'message' => 'Task tag detached successfully.',
            'data' => null,
        ]);
    }
}
