<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_foreign_key_is_enforced(): void
    {
        $this->expectException(QueryException::class);

        Project::factory()->create(['owner_id' => 999999]);
    }

    public function test_project_memberships_are_unique(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $membership = [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('project_user')->insert($membership);

        $this->expectException(QueryException::class);
        DB::table('project_user')->insert($membership);
    }

    public function test_task_assignments_are_unique(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $assignment = [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('task_user')->insert($assignment);

        $this->expectException(QueryException::class);
        DB::table('task_user')->insert($assignment);
    }

    public function test_task_tag_pairs_are_unique(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $tag = Tag::factory()->create(['project_id' => $project->id]);
        $taskTag = [
            'task_id' => $task->id,
            'tag_id' => $tag->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('task_tag')->insert($taskTag);

        $this->expectException(QueryException::class);
        DB::table('task_tag')->insert($taskTag);
    }

    public function test_tag_names_are_unique_within_a_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Tag::factory()->create(['project_id' => $project->id, 'name' => 'backend']);

        $this->expectException(QueryException::class);
        Tag::factory()->create(['project_id' => $project->id, 'name' => 'backend']);
    }

    public function test_deleting_a_project_cascades_project_owned_records(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member, ['joined_at' => now()]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $task->assignees()->attach($member, ['assigned_at' => now()]);
        $tag = Tag::factory()->create(['project_id' => $project->id]);
        $task->tags()->attach($tag);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);

        $project->delete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('task_user', ['task_id' => $task->id]);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('task_tag', ['task_id' => $task->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
