<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * AI トリアージ (緊急度・重要度・4象限判定)
     */
    public function triage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|string',
            'scope' => 'nullable|in:team,personal',
        ]);

        $result = $this->aiService->triage(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['due_date'] ?? null,
            $validated['scope'] ?? 'personal'
        );

        return response()->json($result);
    }

    /**
     * AI タスク自動分解
     */
    public function breakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'methodology' => 'nullable|in:agile,waterfall,matrix',
        ]);

        $result = $this->aiService->breakdown(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['methodology'] ?? 'agile'
        );

        return response()->json($result);
    }

    /**
     * AI 生産性コーチ
     */
    public function coach(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');
        $scope = $request->query('task_scope', 'personal');

        $query = Task::query();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($scope) {
            $query->where('task_scope', $scope);
        }

        $tasks = $query->get();
        $total = $tasks->count();
        $doCount = $tasks->where('priority_type', Task::PRIORITY_URGENT_IMPORTANT)->count();
        $planCount = $tasks->where('priority_type', Task::PRIORITY_NOT_URGENT_IMPORTANT)->count();
        $delegateCount = $tasks->where('priority_type', Task::PRIORITY_URGENT_NOT_IMPORTANT)->count();
        $eliminateCount = $tasks->where('priority_type', Task::PRIORITY_NOT_URGENT_NOT_IMPORTANT)->count();
        $doneCount = $tasks->where('is_completed', true)->count();

        $stats = [
            'total' => $total,
            'completed' => $doneCount,
            'urgent_important' => $doCount,
            'not_urgent_important' => $planCount,
            'urgent_not_important' => $delegateCount,
            'not_urgent_not_important' => $eliminateCount,
            'do' => $doCount,
            'plan' => $planCount,
            'delegate' => $delegateCount,
            'eliminate' => $eliminateCount,
        ];

        $result = $this->aiService->coach($stats);
        $result['stats'] = $stats;

        return response()->json($result);
    }
}
