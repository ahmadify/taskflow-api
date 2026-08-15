<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $this->tokenResponse($user, 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'data' => null,
            ], 401);
        }

        return $this->tokenResponse($user, 'Login successful.');
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
            'data' => null,
        ]);
    }

    private function tokenResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('taskflow-api')->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
