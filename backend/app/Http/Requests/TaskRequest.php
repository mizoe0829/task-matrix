<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPost = $this->isMethod('post');

        return [
            'task_scope' => ['sometimes', Rule::in(['team', 'personal'])],
            'methodology' => ['sometimes', Rule::in(['agile', 'waterfall', 'matrix'])],
            'team_task_id' => ['nullable', 'exists:tasks,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'title' => [$isPost ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority_type' => [
                'sometimes',
                'string',
                Rule::in(Task::PRIORITIES),
            ],
            'status' => ['sometimes', Rule::in(['todo', 'in_progress', 'review', 'done'])],
            'agile_sprint' => ['nullable', 'string', 'max:100'],
            'agile_story_points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'waterfall_phase' => [
                'nullable',
                Rule::in(['requirement', 'design', 'development', 'testing', 'release']),
            ],
            'progress_rate' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'is_completed' => ['sometimes', 'boolean'],
            'google_event_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
