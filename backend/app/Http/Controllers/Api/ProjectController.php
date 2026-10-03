<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects with task metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = Project::withCount([
            'tasks',
            'tasks as completed_tasks_count' => function ($query) {
                $query->where('is_completed', true);
            },
            'tasks as urgent_important_count' => function ($query) {
                $query->where('priority_type', Task::PRIORITY_URGENT_IMPORTANT)->where('is_completed', false);
            },
        ])
        ->orderBy('id', 'desc')
        ->get()
        ->map(function ($project) {
            $total = $project->tasks_count;
            $completed = $project->completed_tasks_count;
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'methodology' => $project->methodology,
                'status' => $project->status,
                'color' => $project->color,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'urgent_important_tasks' => $project->urgent_important_count,
                'progress_percent' => $percent,
                'created_at' => $project->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $projects]);
    }

    /**
     * Overall KPI Summary for Global Dashboard.
     */
    public function summary(): JsonResponse
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $totalTasks = Task::count();
        $completedTasks = Task::where('is_completed', true)->count();
        $urgentTasks = Task::where('priority_type', Task::PRIORITY_URGENT_IMPORTANT)->where('is_completed', false)->count();

        $overallProgress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        return response()->json([
            'data' => [
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'urgent_important_tasks' => $urgentTasks,
                'overall_progress_percent' => $overallProgress,
            ]
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'methodology' => ['required', 'in:agile,waterfall,matrix'],
            'status' => ['sometimes', 'in:active,completed,archived'],
            'color' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $project = Project::create($validated);

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'methodology' => $project->methodology,
                'status' => $project->status,
                'color' => $project->color,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'urgent_important_tasks' => 0,
                'progress_percent' => 0,
                'created_at' => $project->created_at?->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $total = $project->tasks()->count();
        $completed = $project->tasks()->where('is_completed', true)->count();
        $urgent = $project->tasks()->where('priority_type', Task::PRIORITY_URGENT_IMPORTANT)->where('is_completed', false)->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'methodology' => $project->methodology,
                'status' => $project->status,
                'color' => $project->color,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'urgent_important_tasks' => $urgent,
                'progress_percent' => $percent,
                'created_at' => $project->created_at?->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'methodology' => ['sometimes', 'in:agile,waterfall,matrix'],
            'status' => ['sometimes', 'in:active,completed,archived'],
            'color' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $project->update($validated);

        return response()->json(['data' => $project]);
    }

    /**
     * Remove the specified project and related tasks.
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project and its tasks deleted successfully']);
    }
}
