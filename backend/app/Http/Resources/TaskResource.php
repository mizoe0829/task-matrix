<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_scope' => $this->task_scope ?? 'personal',
            'methodology' => $this->methodology ?? 'matrix',
            'team_task_id' => $this->team_task_id,
            'team_task_title' => $this->teamTask?->title,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'priority_type' => $this->priority_type,
            'status' => $this->status ?? 'todo',
            'agile_sprint' => $this->agile_sprint,
            'agile_story_points' => $this->agile_story_points,
            'waterfall_phase' => $this->waterfall_phase,
            'progress_rate' => $this->progress_rate ?? 0,
            'start_date' => $this->start_date ? $this->start_date->timezone('Asia/Tokyo')->toIso8601String() : null,
            'due_date' => $this->due_date ? $this->due_date->timezone('Asia/Tokyo')->toIso8601String() : null,
            'is_completed' => (bool) $this->is_completed,
            'google_event_id' => $this->google_event_id,
            'created_at' => $this->created_at ? $this->created_at->timezone('Asia/Tokyo')->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->timezone('Asia/Tokyo')->toIso8601String() : null,
        ];
    }
}
