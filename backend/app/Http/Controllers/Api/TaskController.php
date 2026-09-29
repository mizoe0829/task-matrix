<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports filtering by task_scope, methodology, priority_type, status, etc.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::with('teamTask');

        if ($request->filled('task_scope')) {
            $query->where('task_scope', $request->query('task_scope'));
        }

        if ($request->filled('methodology')) {
            $query->where('methodology', $request->query('methodology'));
        }

        if ($request->filled('priority_type')) {
            $query->where('priority_type', $request->query('priority_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        if ($request->filled('agile_sprint')) {
            $query->where('agile_sprint', $request->query('agile_sprint'));
        }

        if ($request->filled('waterfall_phase')) {
            $query->where('waterfall_phase', $request->query('waterfall_phase'));
        }

        if ($request->has('is_completed')) {
            $query->where('is_completed', filter_var($request->query('is_completed'), FILTER_VALIDATE_BOOLEAN));
        }

        $tasks = $query->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Sync status with is_completed
        if (isset($validated['status']) && !isset($validated['is_completed'])) {
            $validated['is_completed'] = ($validated['status'] === Task::STATUS_DONE);
        } elseif (isset($validated['is_completed']) && !isset($validated['status'])) {
            $validated['status'] = $validated['is_completed'] ? Task::STATUS_DONE : Task::STATUS_TODO;
        }

        $task = Task::create($validated);
        $task->load('teamTask');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        $task->load('teamTask');
        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, Task $task): TaskResource
    {
        $validated = $request->validated();

        if (isset($validated['status']) && !isset($validated['is_completed'])) {
            $validated['is_completed'] = ($validated['status'] === Task::STATUS_DONE);
        } elseif (isset($validated['is_completed']) && !isset($validated['status'])) {
            $validated['status'] = $validated['is_completed'] ? Task::STATUS_DONE : Task::STATUS_TODO;
        }

        $task->update($validated);
        $task->load('teamTask');

        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }

    /**
     * Import/Branch a team task into a personal task (チームタスクから個人タスクを生成)
     */
    public function branchToPersonal(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'priority_type' => ['required', 'string'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $personalTask = Task::create([
            'task_scope' => Task::SCOPE_PERSONAL,
            'methodology' => Task::METHODOLOGY_MATRIX,
            'team_task_id' => $task->id,
            'assigned_to' => $request->input('assigned_to', $task->assigned_to ?? '自分'),
            'title' => $request->input('title', $task->title),
            'description' => $request->input('description', "【チームタスクより取り込み】\n" . ($task->description ?? '')),
            'priority_type' => $request->input('priority_type', Task::PRIORITY_URGENT_IMPORTANT),
            'status' => Task::STATUS_TODO,
            'due_date' => $request->input('due_date', $task->due_date),
            'is_completed' => false,
        ]);

        $personalTask->load('teamTask');

        return (new TaskResource($personalTask))
            ->response()
            ->setStatusCode(201);
    }
}
