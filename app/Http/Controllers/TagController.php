<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        $tags = Tag::query()
            ->whereHas('project', function ($query) use ($request) {
                $query->where(function ($projects) use ($request) {
                    $projects->where('owner_id', $request->user()->id)
                        ->orWhereHas('members', fn ($members) => $members->whereKey($request->user()->id));
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => [
                'tags' => TagResource::collection($tags->getCollection()),
            ],
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $project = Project::findOrFail($validated['project_id']);

        $this->authorize('create', [Tag::class, $project]);

        $tag = $project->tags()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        return response()->json([
            'message' => 'Tag created successfully.',
            'data' => [
                'tag' => new TagResource($tag),
            ],
        ], 201);
    }
}
