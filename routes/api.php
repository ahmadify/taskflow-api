<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskAssigneeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTagController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::patch('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember']);
    Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);

    Route::scopeBindings()->group(function () {
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::get('/projects/{project}/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/projects/{project}/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy']);

        Route::post('/projects/{project}/tasks/{task}/assignees', [TaskAssigneeController::class, 'store']);
        Route::delete('/projects/{project}/tasks/{task}/assignees/{user}', [TaskAssigneeController::class, 'destroy']);

        Route::post('/projects/{project}/tasks/{task}/tags', [TaskTagController::class, 'store']);
        Route::delete('/projects/{project}/tasks/{task}/tags/{tag}', [TaskTagController::class, 'destroy']);

        Route::get('/projects/{project}/tasks/{task}/comments', [CommentController::class, 'index']);
        Route::post('/projects/{project}/tasks/{task}/comments', [CommentController::class, 'store']);
        Route::patch('/projects/{project}/tasks/{task}/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/projects/{project}/tasks/{task}/comments/{comment}', [CommentController::class, 'destroy']);
    });
});
