<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_is_required_for_every_project_endpoint(): void
    {
        $requests = [
            fn () => $this->getJson('/api/projects'),
            fn () => $this->postJson('/api/projects'),
            fn () => $this->getJson('/api/projects/1'),
            fn () => $this->patchJson('/api/projects/1'),
            fn () => $this->deleteJson('/api/projects/1'),
            fn () => $this->postJson('/api/projects/1/members'),
            fn () => $this->deleteJson('/api/projects/1/members/1'),
        ];

        foreach ($requests as $request) {
            $request()->assertUnauthorized();
        }
    }

    public function test_an_authenticated_user_can_create_a_project(): void
    {
        $owner = User::factory()->create();

        $response = $this->withToken($this->tokenFor($owner))->postJson('/api/projects', [
            'name' => 'Website Redesign',
            'description' => 'Coordinate the website redesign.',
            'start_date' => '2026-08-15',
            'due_date' => '2026-10-15',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Project created successfully.')
            ->assertJsonPath('data.project.name', 'Website Redesign')
            ->assertJsonPath('data.project.owner.id', $owner->id)
            ->assertJsonPath('data.project.members.0.id', $owner->id)
            ->assertJsonPath('data.project.members.0.membership.role', 'owner')
            ->assertJsonMissingPath('data.project.owner.password')
            ->assertJsonMissingPath('data.project.members.0.remember_token');

        $projectId = $response->json('data.project.id');

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'owner_id' => $owner->id,
            'name' => 'Website Redesign',
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $projectId,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_project_creation_validates_input(): void
    {
        $owner = User::factory()->create();

        $this->withToken($this->tokenFor($owner))->postJson('/api/projects', [
            'name' => '',
            'description' => ['invalid'],
            'start_date' => 'invalid-date',
            'due_date' => 'invalid-date',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'start_date', 'due_date']);
    }

    public function test_an_owner_sees_owned_projects(): void
    {
        $owner = User::factory()->create();
        $ownedProjects = Project::factory()->count(2)->create(['owner_id' => $owner->id]);
        Project::factory()->create();

        $response = $this->withToken($this->tokenFor($owner))->getJson('/api/projects');

        $response->assertOk()->assertJsonPath('meta.total', 2);

        $projectIds = collect($response->json('data.projects'))->pluck('id');

        $this->assertEqualsCanonicalizing($ownedProjects->modelKeys(), $projectIds->all());
    }

    public function test_a_member_sees_shared_projects(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->withToken($this->tokenFor($member))->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.projects.0.id', $project->id);
    }

    public function test_unrelated_projects_are_excluded_from_the_listing(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->create();

        $this->withToken($this->tokenFor($user))->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data.projects');
    }

    public function test_an_owner_can_view_a_project_with_membership_information(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->withToken($this->tokenFor($owner))->getJson("/api/projects/{$project->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.project.owner.id', $owner->id)
            ->assertJsonPath('data.project.members_count', 2)
            ->assertJsonCount(2, 'data.project.members')
            ->assertJsonMissingPath('data.project.owner.password');

        $roles = collect($response->json('data.project.members'))->pluck('membership.role', 'id');

        $this->assertSame('owner', $roles[$owner->id]);
        $this->assertSame('member', $roles[$member->id]);
    }

    public function test_a_member_can_view_a_shared_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $this->withToken($this->tokenFor($member))->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.project.id', $project->id);
    }

    public function test_an_unrelated_user_cannot_view_a_project(): void
    {
        $owner = User::factory()->create();
        $unrelated = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->withToken($this->tokenFor($unrelated))->getJson("/api/projects/{$project->id}")
            ->assertForbidden();
    }

    public function test_an_owner_can_update_a_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->withToken($this->tokenFor($owner))->patchJson("/api/projects/{$project->id}", [
            'name' => 'Updated Project',
            'description' => null,
            'due_date' => '2026-12-31',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Project updated successfully.')
            ->assertJsonPath('data.project.name', 'Updated Project')
            ->assertJsonPath('data.project.description', null)
            ->assertJsonPath('data.project.due_date', '2026-12-31');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
            'description' => null,
        ]);
    }

    public function test_an_owner_can_delete_a_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->withToken($this->tokenFor($owner))->deleteJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Project deleted successfully.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id]);
    }

    public function test_a_member_cannot_update_or_delete_a_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);
        $token = $this->tokenFor($member);

        $this->withToken($token)->patchJson("/api/projects/{$project->id}", ['name' => 'Rejected'])
            ->assertForbidden();
        $this->withToken($token)->deleteJson("/api/projects/{$project->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_an_unrelated_user_cannot_update_or_delete_a_project(): void
    {
        $owner = User::factory()->create();
        $unrelated = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $token = $this->tokenFor($unrelated);

        $this->withToken($token)->patchJson("/api/projects/{$project->id}", ['name' => 'Rejected'])
            ->assertForbidden();
        $this->withToken($token)->deleteJson("/api/projects/{$project->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_an_owner_can_add_a_project_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->withToken($this->tokenFor($owner))->postJson("/api/projects/{$project->id}/members", [
            'user_id' => $member->id,
            'role' => 'member',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Project member added successfully.')
            ->assertJsonPath('data.project.members_count', 2);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_duplicate_project_membership_is_rejected(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $this->withToken($this->tokenFor($owner))->postJson("/api/projects/{$project->id}/members", [
            'user_id' => $member->id,
            'role' => 'member',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('project_user', 2);
    }

    public function test_an_owner_can_remove_a_project_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

        $this->withToken($this->tokenFor($owner))
            ->deleteJson("/api/projects/{$project->id}/members/{$member->id}")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Project member removed successfully.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_a_non_owner_cannot_manage_project_membership(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $candidate = User::factory()->create();
        $project = $this->createProjectFor($owner);
        $project->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);
        $token = $this->tokenFor($member);

        $this->withToken($token)->postJson("/api/projects/{$project->id}/members", [
            'user_id' => $candidate->id,
            'role' => 'member',
        ])->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/projects/{$project->id}/members/{$member->id}")
            ->assertForbidden();

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $candidate->id,
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_the_project_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectFor($owner);

        $this->withToken($this->tokenFor($owner))
            ->deleteJson("/api/projects/{$project->id}/members/{$owner->id}")
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The project owner cannot be removed.',
                'data' => null,
            ]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    private function createProjectFor(User $owner): Project
    {
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($owner, ['role' => 'owner', 'joined_at' => now()]);

        return $project;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }
}
