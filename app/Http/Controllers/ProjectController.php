<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->where(function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id)
                    ->orWhereHas('members', fn ($members) => $members->whereKey($request->user()->id));
            })
            ->with('owner')
            ->withCount('members')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => [
                'projects' => ProjectResource::collection($projects->getCollection()),
            ],
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
            'links' => [
                'first' => $projects->url(1),
                'last' => $projects->url($projects->lastPage()),
                'previous' => $projects->previousPageUrl(),
                'next' => $projects->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = $request->user()->ownedProjects()->create($request->validated());
            $project->members()->attach($request->user(), [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $project;
        });

        return $this->projectResponse($project, 'Project created successfully.', 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return $this->projectResponse($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return $this->projectResponse($project, 'Project updated successfully.');
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
            'data' => null,
        ]);
    }

    public function addMember(AddProjectMemberRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();

        $project->members()->attach($validated['user_id'], [
            'role' => $validated['role'] ?? 'member',
            'joined_at' => now(),
        ]);

        return $this->projectResponse($project, 'Project member added successfully.', 201);
    }

    public function removeMember(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if ($project->owner_id === $user->id) {
            return response()->json([
                'message' => 'The project owner cannot be removed.',
                'data' => null,
            ], 422);
        }

        if (! $project->members()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'The user is not a member of this project.',
                'data' => null,
            ], 404);
        }

        $project->members()->detach($user->id);

        return response()->json([
            'message' => 'Project member removed successfully.',
            'data' => null,
        ]);
    }

    private function projectResponse(Project $project, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'data' => [
                'project' => new ProjectResource(
                    $project->load(['owner', 'members'])->loadCount('members'),
                ),
            ],
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }
}
