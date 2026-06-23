<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\DialogController;
use App\Http\Controllers\Api\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('captcha', [AuthController::class, 'captcha']);
    Route::post('send-code', [AuthController::class, 'sendCode']);
    Route::post('verify-code', [AuthController::class, 'verifyCode']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Protected routes (authentication required)
Route::middleware('auth.token')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // User
    Route::prefix('user')->group(function () {
        Route::get('info', [UserController::class, 'info']);
        Route::post('update', [UserController::class, 'update']);
        Route::post('password', [UserController::class, 'updatePassword']);
        Route::get('list', [UserController::class, 'list']);
        Route::get('search', [UserController::class, 'search']);
        Route::get('departments', [UserController::class, 'departments']);
        Route::get('show/{id}', [UserController::class, 'show']);
    });

    // Project
    Route::prefix('project')->group(function () {
        Route::get('lists', [ProjectController::class, 'lists']);
        Route::post('create', [ProjectController::class, 'create']);
        Route::get('{id}', [ProjectController::class, 'show']);
        Route::post('{id}', [ProjectController::class, 'update']);
        Route::delete('{id}', [ProjectController::class, 'delete']);
        Route::post('{id}/archive', [ProjectController::class, 'archive']);
        Route::post('{id}/unarchive', [ProjectController::class, 'unarchive']);
        Route::post('{id}/member/add', [ProjectController::class, 'addMember']);
        Route::post('{id}/member/remove', [ProjectController::class, 'removeMember']);
        Route::post('{id}/column/create', [ProjectController::class, 'createColumn']);
        Route::post('{id}/column/{columnId}', [ProjectController::class, 'updateColumn']);
        Route::delete('{id}/column/{columnId}', [ProjectController::class, 'deleteColumn']);
        Route::post('{id}/tag/create', [ProjectController::class, 'createTag']);
        Route::get('{id}/tags', [ProjectController::class, 'tags']);
    });

    // Task
    Route::prefix('task')->group(function () {
        Route::get('my', [TaskController::class, 'myTasks']);
        Route::post('create/{projectId}', [TaskController::class, 'create']);
        Route::get('{projectId}/{id}', [TaskController::class, 'show']);
        Route::post('{projectId}/{id}', [TaskController::class, 'update']);
        Route::delete('{projectId}/{id}', [TaskController::class, 'delete']);
        Route::post('{projectId}/{id}/move', [TaskController::class, 'move']);
        Route::post('{projectId}/{id}/assign', [TaskController::class, 'assign']);
        Route::get('{projectId}/column/{columnId}', [TaskController::class, 'columnTasks']);
    });

    // Dialog
    Route::prefix('dialog')->group(function () {
        Route::get('lists', [DialogController::class, 'lists']);
        Route::post('create', [DialogController::class, 'create']);
        Route::get('{id}', [DialogController::class, 'show']);
        Route::get('{id}/messages', [DialogController::class, 'messages']);
        Route::post('{id}/message', [DialogController::class, 'sendMessage']);
        Route::post('{id}/message/{msgId}/recall', [DialogController::class, 'recallMessage']);
        Route::delete('{id}/message/{msgId}', [DialogController::class, 'deleteMessage']);
        Route::post('{id}/member/add', [DialogController::class, 'addMember']);
        Route::post('{id}/member/remove', [DialogController::class, 'removeMember']);
        Route::post('{id}/leave', [DialogController::class, 'leave']);
    });

    // File
    Route::prefix('file')->group(function () {
        Route::post('upload', [FileController::class, 'upload']);
        Route::post('upload-multiple', [FileController::class, 'uploadMultiple']);
        Route::get('list', [FileController::class, 'list']);
        Route::get('{id}', [FileController::class, 'show']);
        Route::get('{id}/download', [FileController::class, 'download']);
        Route::get('{id}/preview', [FileController::class, 'preview']);
        Route::delete('{id}', [FileController::class, 'delete']);
        Route::post('batch-delete', [FileController::class, 'batchDelete']);
    });

    // Health check
    Route::get('health', function () {
        return response()->json([
            'ret' => 1,
            'msg' => 'OK',
            'data' => [
                'status' => 'healthy',
                'timestamp' => time(),
            ]
        ]);
    });
});
