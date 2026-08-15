<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('data.user.name', 'Taylor Example')
            ->assertJsonPath('data.user.email', 'taylor@example.com')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');

        $user = User::where('email', 'taylor@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_returns_validation_errors(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'password_confirmation']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'member@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Another Member',
            'email' => 'MEMBER@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_password_confirmation_to_match(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Taylor Example',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_a_user_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secure-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'member@example.com',
            'password' => 'secure-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['message', 'data' => ['user', 'token', 'token_type']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secure-password'),
        ]);

        $expectedResponse = [
            'message' => 'The provided credentials are incorrect.',
            'data' => null,
        ];

        $this->postJson('/api/login', [
            'email' => 'member@example.com',
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson($expectedResponse);

        $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson($expectedResponse);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_authenticated_user_can_access_the_user_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');
    }

    public function test_the_user_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_logout_deletes_only_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current-token');
        $otherToken = $user->createToken('other-token');

        $response = $this->withToken($currentToken->plainTextToken)->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'Logout successful.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    public function test_a_logged_out_token_can_no_longer_authenticate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        Auth::forgetGuards();

        $this->withToken($token)->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
