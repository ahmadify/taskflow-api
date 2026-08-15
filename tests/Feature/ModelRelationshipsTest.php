<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_has_an_owner_and_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $project->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($project->owner->is($owner));
        $this->assertTrue($owner->ownedProjects->contains($project));
        $this->assertTrue($member->projects->contains($project));
        $this->assertSame('owner', $project->members()->findOrFail($owner->id)->pivot->role);
        $this->assertCount(2, $project->members);
    }

    public function test_a_project_contains_tasks_created_by_users(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $this->assertTrue($project->tasks->contains($task));
        $this->assertTrue($task->project->is($project));
        $this->assertTrue($task->creator->is($owner));
        $this->assertTrue($owner->createdTasks->contains($task));
    }

    public function test_users_can_be_assigned_to_tasks(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);

        $task->assignees()->attach($assignee, ['assigned_at' => now()]);

        $this->assertTrue($task->assignees->contains($assignee));
        $this->assertTrue($assignee->assignedTasks->contains($task));
        $this->assertNotNull($task->assignees()->findOrFail($assignee->id)->pivot->assigned_at);
    }

    public function test_tags_can_be_attached_to_tasks(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $tag = Tag::factory()->create(['project_id' => $project->id]);

        $task->tags()->attach($tag);

        $this->assertTrue($task->tags->contains($tag));
        $this->assertTrue($tag->tasks->contains($task));
        $this->assertTrue($project->tags->contains($tag));
    }

    public function test_users_can_add_comments_to_tasks(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->assertTrue($task->comments->contains($comment));
        $this->assertTrue($comment->task->is($task));
        $this->assertTrue($comment->user->is($owner));
        $this->assertTrue($owner->comments->contains($comment));
    }
}
