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
     * Supports filtering by priority_type and is_completed.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::query();

        if ($request->filled('priority_type')) {
            $query->where('priority_type', $request->query('priority_type'));
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
        $task = Task::create($validated);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

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
}
