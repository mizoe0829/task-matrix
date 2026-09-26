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
            'title' => $this->title,
            'description' => $this->description,
            'priority_type' => $this->priority_type,
            'due_date' => $this->due_date ? $this->due_date->timezone('Asia/Tokyo')->toIso8601String() : null,
            'is_completed' => (bool) $this->is_completed,
            'google_event_id' => $this->google_event_id,
            'created_at' => $this->created_at ? $this->created_at->timezone('Asia/Tokyo')->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->timezone('Asia/Tokyo')->toIso8601String() : null,
        ];
    }
}
