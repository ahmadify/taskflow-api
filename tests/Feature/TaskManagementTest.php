<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_is_required_for_every_task_collaboration_endpoint(): void
    {
        $requests = [
            fn () => $this->getJson('/api/projects/1/tasks'),
            fn () => $this->postJson('/api/projects/1/tasks'),
            fn () => $this->getJson('/api/projects/1/tasks/1'),
            fn () => $this->patchJson('/api/projects/1/tasks/1'),
            fn () => $this->deleteJson('/api/projects/1/tasks/1'),
            fn () => $this->postJson('/api/projects/1/tasks/1/assignees'),
            fn () => $this->deleteJson('/api/projects/1/tasks/1/assignees/1'),
            fn () => $this->getJson('/api/tags'),
            fn () => $this->postJson('/api/tags'),
            fn () => $this->postJson('/api/projects/1/tasks/1/tags'),
            fn () => $this->deleteJson('/api/projects/1/tasks/1/tags/1'),
            fn () => $this->getJson('/api/projects/1/tasks/1/comments'),
            fn () => $this->postJson('/api/projects/1/tasks/1/comments'),
            fn () => $this->patchJson('/api/projects/1/tasks/1/comments/1'),
            fn () => $this->deleteJson('/api/projects/1/tasks/1/comments/1'),
        ];

        foreach ($requests as $request) {
            $request()->assertUnauthorized();
        }
    }

    public function test_a_project_owner_can_create_a_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $response = $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Prepare release notes',
            'description' => 'Summarize the release changes.',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => '2026-09-01',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Task created successfully.')
            ->assertJsonPath('data.task.project_id', $project->id)
            ->assertJsonPath('data.task.title', 'Prepare release notes')
            ->assertJsonPath('data.task.status', 'in_progress')
            ->assertJsonPath('data.task.priority', 'high')
            ->assertJsonPath('data.task.creator.id', $owner->id)
            ->assertJsonMissingPath('data.task.creator.password');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'title' => 'Prepare release notes',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_task_creation_validates_documented_fields(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks", [
            'title' => '',
            'description' => ['invalid'],
            'status' => 'unknown',
            'priority' => 'urgent',
            'due_date' => 'invalid-date',
            'completed_at' => 'invalid-date',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'status',
                'priority',
                'due_date',
                'completed_at',
            ]);
    }

    public function test_project_participants_can_list_and_view_tasks_but_unrelated_users_cannot(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $assignee = User::factory()->create();
        $unrelated = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $this->addMember($project, $assignee);
        $task = $this->createTaskFor($project, $owner);
        $task->assignees()->attach($assignee, ['assigned_at' => now()]);

        foreach ([$owner, $member, $assignee] as $participant) {
            $this->requestAs($participant)->getJson("/api/projects/{$project->id}/tasks")
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.tasks.0.id', $task->id);

            $this->requestAs($participant)->getJson("/api/projects/{$project->id}/tasks/{$task->id}")
                ->assertOk()
                ->assertJsonPath('data.task.id', $task->id);
        }

        $this->requestAs($unrelated)->getJson("/api/projects/{$project->id}/tasks")
            ->assertForbidden();
        $this->requestAs($unrelated)->getJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_a_project_owner_can_update_a_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($owner)->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", [
            'title' => 'Updated task title',
            'status' => 'completed',
            'priority' => 'low',
            'completed_at' => '2026-08-15 12:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Task updated successfully.')
            ->assertJsonPath('data.task.title', 'Updated task title')
            ->assertJsonPath('data.task.status', 'completed')
            ->assertJsonPath('data.task.priority', 'low');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated task title',
            'status' => 'completed',
            'priority' => 'low',
        ]);
    }

    public function test_a_project_owner_can_delete_a_task(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($owner)->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Task deleted successfully.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_project_members_and_assignees_cannot_manage_tasks(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $this->addMember($project, $assignee);
        $task = $this->createTaskFor($project, $owner);
        $task->assignees()->attach($assignee, ['assigned_at' => now()]);

        foreach ([$member, $assignee] as $participant) {
            $this->requestAs($participant)->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Rejected task',
            ])->assertForbidden();

            $this->requestAs($participant)->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Rejected update',
            ])->assertForbidden();

            $this->requestAs($participant)->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}")
                ->assertForbidden();
        }

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_nested_project_task_mismatches_are_rejected(): void
    {
        $owner = User::factory()->create();
        $firstProject = $this->createProjectFor($owner);
        $secondProject = $this->createProjectFor($owner);
        $task = $this->createTaskFor($firstProject, $owner);

        $this->requestAs($owner)->getJson("/api/projects/{$secondProject->id}/tasks/{$task->id}")
            ->assertNotFound();
        $this->requestAs($owner)->patchJson("/api/projects/{$secondProject->id}/tasks/{$task->id}", [
            'title' => 'Rejected update',
        ])->assertNotFound();
    }

    private function createProjectFor(User $owner): Project
    {
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

        return $project;
    }

    private function createTaskFor(Project $project, User $creator): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
        ]);
    }

    private function addMember(Project $project, User $user): void
    {
        $project->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);
    }

    private function requestAs(User $user): static
    {
        Auth::forgetGuards();

        return $this->withToken($user->createToken('test-token')->plainTextToken);
    }
}
