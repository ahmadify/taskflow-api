<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TaskCollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_assign_and_remove_the_project_owner_and_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees", [
            'user_id' => $owner->id,
        ])->assertCreated();

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees", [
            'user_id' => $member->id,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Task assignee added successfully.')
            ->assertJsonPath('data.task.assignees.1.assignment.assigned_at', fn ($value) => $value !== null);

        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $owner->id]);
        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $member->id]);

        $this->requestAs($owner)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task assignee removed successfully.');

        $this->assertDatabaseMissing('task_user', ['task_id' => $task->id, 'user_id' => $member->id]);
    }

    public function test_assignment_rejects_users_outside_the_project(): void
    {
        $owner = User::factory()->create();
        $outsideUser = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees", [
            'user_id' => $outsideUser->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseMissing('task_user', ['task_id' => $task->id, 'user_id' => $outsideUser->id]);
    }

    public function test_duplicate_task_assignments_are_rejected(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $task = $this->createTaskFor($project, $owner);
        $task->assignees()->attach($member, ['assigned_at' => now()]);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees", [
            'user_id' => $member->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('task_user', 1);
    }

    public function test_project_members_cannot_manage_assignments_or_task_tags(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $candidate = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $this->addMember($project, $candidate);
        $task = $this->createTaskFor($project, $owner);
        $task->assignees()->attach($candidate, ['assigned_at' => now()]);
        $tag = Tag::factory()->create(['project_id' => $project->id]);
        $task->tags()->attach($tag);

        $this->requestAs($member)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees", [
            'user_id' => $member->id,
        ])->assertForbidden();

        $this->requestAs($member)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/assignees/{$candidate->id}")
            ->assertForbidden();

        $this->requestAs($member)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/tags", [
            'tag_id' => $tag->id,
        ])->assertForbidden();

        $this->requestAs($member)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/tags/{$tag->id}")
            ->assertForbidden();
    }

    public function test_project_owners_can_create_tags_and_participants_only_list_accessible_tags(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $unrelated = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $unrelatedProject = $this->createProjectFor($unrelated);
        $unrelatedTag = Tag::factory()->create(['project_id' => $unrelatedProject->id]);

        $response = $this->requestAs($owner)->postJson('/api/tags', [
            'project_id' => $project->id,
            'name' => 'backend',
            'color' => '#336699',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Tag created successfully.')
            ->assertJsonPath('data.tag.project_id', $project->id)
            ->assertJsonPath('data.tag.name', 'backend');

        $tagId = $response->json('data.tag.id');

        foreach ([$owner, $member] as $participant) {
            $list = $this->requestAs($participant)->getJson('/api/tags')->assertOk();
            $this->assertContains($tagId, collect($list->json('data.tags'))->pluck('id'));
            $this->assertNotContains($unrelatedTag->id, collect($list->json('data.tags'))->pluck('id'));
        }

        $unrelatedList = $this->requestAs($unrelated)->getJson('/api/tags')->assertOk();
        $this->assertSame([$unrelatedTag->id], collect($unrelatedList->json('data.tags'))->pluck('id')->all());
    }

    public function test_non_owners_cannot_create_tags_and_duplicate_names_are_rejected(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        Tag::factory()->create(['project_id' => $project->id, 'name' => 'backend']);

        $this->requestAs($member)->postJson('/api/tags', [
            'project_id' => $project->id,
            'name' => 'frontend',
        ])->assertForbidden();

        $this->requestAs($owner)->postJson('/api/tags', [
            'project_id' => $project->id,
            'name' => 'backend',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_an_owner_can_attach_and_detach_project_tags(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);
        $tag = Tag::factory()->create(['project_id' => $project->id]);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/tags", [
            'tag_id' => $tag->id,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Task tag attached successfully.')
            ->assertJsonPath('data.task.tags.0.id', $tag->id);

        $this->assertDatabaseHas('task_tag', ['task_id' => $task->id, 'tag_id' => $tag->id]);

        $this->requestAs($owner)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task tag detached successfully.');

        $this->assertDatabaseMissing('task_tag', ['task_id' => $task->id, 'tag_id' => $tag->id]);
    }

    public function test_duplicate_and_cross_project_tag_attachments_are_rejected(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $otherProject = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);
        $tag = Tag::factory()->create(['project_id' => $project->id]);
        $otherTag = Tag::factory()->create(['project_id' => $otherProject->id]);
        $task->tags()->attach($tag);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/tags", [
            'tag_id' => $tag->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tag_id');

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/tags", [
            'tag_id' => $otherTag->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tag_id');
    }

    public function test_project_members_can_create_and_list_task_comments(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $task = $this->createTaskFor($project, $owner);

        $response = $this->requestAs($member)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/comments", [
            'body' => 'I have started working on this task.',
            'user_id' => $owner->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Comment created successfully.')
            ->assertJsonPath('data.comment.body', 'I have started working on this task.')
            ->assertJsonPath('data.comment.user.id', $member->id)
            ->assertJsonMissingPath('data.comment.user.password');

        $commentId = $response->json('data.comment.id');

        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);

        $this->requestAs($owner)->getJson("/api/projects/{$project->id}/tasks/{$task->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.comments.0.id', $commentId);
    }

    public function test_comments_require_non_empty_content(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($owner)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/comments", [
            'body' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_comment_authors_can_update_and_delete_their_comments(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $task = $this->createTaskFor($project, $owner);
        $comment = $this->createCommentFor($task, $member);

        $this->requestAs($member)
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}", [
                'body' => 'Updated comment body.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Comment updated successfully.')
            ->assertJsonPath('data.comment.body', 'Updated comment body.');

        $this->requestAs($member)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Comment deleted successfully.');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_project_owners_can_delete_but_not_edit_other_users_comments(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $member);
        $task = $this->createTaskFor($project, $owner);
        $comment = $this->createCommentFor($task, $member);

        $this->requestAs($owner)
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}", [
                'body' => 'Rejected moderation edit.',
            ])->assertForbidden();

        $this->requestAs($owner)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_users_cannot_modify_another_members_comment(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $otherMember = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $this->addMember($project, $author);
        $this->addMember($project, $otherMember);
        $task = $this->createTaskFor($project, $owner);
        $comment = $this->createCommentFor($task, $author);

        $this->requestAs($otherMember)
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}", [
                'body' => 'Rejected edit.',
            ])->assertForbidden();

        $this->requestAs($otherMember)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}/comments/{$comment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_unrelated_users_cannot_access_or_create_task_comments(): void
    {
        $owner = User::factory()->create();
        $unrelated = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $task = $this->createTaskFor($project, $owner);

        $this->requestAs($unrelated)
            ->getJson("/api/projects/{$project->id}/tasks/{$task->id}/comments")
            ->assertForbidden();

        $this->requestAs($unrelated)
            ->postJson("/api/projects/{$project->id}/tasks/{$task->id}/comments", ['body' => 'Rejected'])
            ->assertForbidden();
    }

    public function test_nested_task_comment_mismatches_are_rejected(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $firstTask = $this->createTaskFor($project, $owner);
        $secondTask = $this->createTaskFor($project, $owner);
        $comment = $this->createCommentFor($firstTask, $owner);

        $this->requestAs($owner)
            ->patchJson("/api/projects/{$project->id}/tasks/{$secondTask->id}/comments/{$comment->id}", [
                'body' => 'Rejected update.',
            ])->assertNotFound();

        $this->requestAs($owner)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$secondTask->id}/comments/{$comment->id}")
            ->assertNotFound();
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

    private function createCommentFor(Task $task, User $user): Comment
    {
        return Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
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
