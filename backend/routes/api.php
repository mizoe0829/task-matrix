<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('tasks/{task}/branch-to-personal', [TaskController::class, 'branchToPersonal']);
Route::apiResource('tasks', TaskController::class);
