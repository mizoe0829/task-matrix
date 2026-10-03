<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// AI Integration API
Route::prefix('ai')->group(function () {
    Route::post('triage', [AiController::class, 'triage']);
    Route::post('breakdown', [AiController::class, 'breakdown']);
    Route::get('coach', [AiController::class, 'coach']);
});

// Projects API
Route::get('projects/summary', [ProjectController::class, 'summary']);
Route::apiResource('projects', ProjectController::class);

// Tasks API
Route::post('tasks/{task}/branch-to-personal', [TaskController::class, 'branchToPersonal']);
Route::apiResource('tasks', TaskController::class);
