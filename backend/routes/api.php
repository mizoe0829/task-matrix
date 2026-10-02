<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Projects API
Route::get('projects/summary', [ProjectController::class, 'summary']);
Route::apiResource('projects', ProjectController::class);

// Tasks API
Route::post('tasks/{task}/branch-to-personal', [TaskController::class, 'branchToPersonal']);
Route::apiResource('tasks', TaskController::class);
