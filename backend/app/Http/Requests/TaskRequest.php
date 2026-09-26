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
            'title' => [$isPost ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority_type' => [
                $isPost ? 'required' : 'sometimes',
                'string',
                Rule::in(Task::PRIORITIES),
            ],
            'due_date' => ['nullable', 'date'],
            'is_completed' => ['sometimes', 'boolean'],
            'google_event_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
