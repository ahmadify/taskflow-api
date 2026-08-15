<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('viewAny', [Comment::class, $task]);

        $comments = $task->comments()
            ->with('user')
            ->oldest()
            ->paginate(20);

        return response()->json([
            'data' => [
                'comments' => CommentResource::collection($comments->getCollection()),
            ],
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request, Project $project, Task $task): JsonResponse
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return response()->json([
            'message' => 'Comment created successfully.',
            'data' => [
                'comment' => new CommentResource($comment->load('user')),
            ],
        ], 201);
    }

    public function update(
        UpdateCommentRequest $request,
        Project $project,
        Task $task,
        Comment $comment,
    ): JsonResponse {
        $comment->update($request->validated());

        return response()->json([
            'message' => 'Comment updated successfully.',
            'data' => [
                'comment' => new CommentResource($comment->load('user')),
            ],
        ]);
    }

    public function destroy(Project $project, Task $task, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
            'data' => null,
        ]);
    }
}
