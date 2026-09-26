<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching task list.
     */
    public function test_can_get_task_list(): void
    {
        Task::create([
            'title' => 'タスク1',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
        ]);
        Task::create([
            'title' => 'タスク2',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'priority_type',
                        'due_date',
                        'is_completed',
                        'google_event_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * Test filtering tasks by priority_type.
     */
    public function test_can_filter_tasks_by_priority_type(): void
    {
        Task::create([
            'title' => '緊急重要タスク',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
        ]);
        Task::create([
            'title' => '非緊急重要タスク',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
        ]);

        $response = $this->getJson('/api/tasks?priority_type=urgent_important');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '緊急重要タスク');
    }

    /**
     * Test filtering tasks by completion status.
     */
    public function test_can_filter_tasks_by_is_completed(): void
    {
        Task::create([
            'title' => '完了タスク',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
            'is_completed' => true,
        ]);
        Task::create([
            'title' => '未完了タスク',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
            'is_completed' => false,
        ]);

        $response = $this->getJson('/api/tasks?is_completed=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '完了タスク');
    }

    /**
     * Test creating a task successfully.
     */
    public function test_can_create_task(): void
    {
        $payload = [
            'title' => '新規プロジェクト企画書作成',
            'description' => '第2象限の最重要タスク',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
            'due_date' => '2026-10-01 18:00:00',
            'is_completed' => false,
        ];

        $response = $this->postJson('/api/tasks', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', '新規プロジェクト企画書作成')
            ->assertJsonPath('data.priority_type', Task::PRIORITY_NOT_URGENT_IMPORTANT);

        $this->assertDatabaseHas('tasks', [
            'title' => '新規プロジェクト企画書作成',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
        ]);
    }

    /**
     * Test validation error on task creation.
     */
    public function test_create_task_validation_fails(): void
    {
        $response = $this->postJson('/api/tasks', [
            // title missing
            'priority_type' => 'invalid_priority_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority_type']);
    }

    /**
     * Test fetching a single task detail.
     */
    public function test_can_show_task(): void
    {
        $task = Task::create([
            'title' => '詳細タスク',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', '詳細タスク');
    }

    /**
     * Test updating a task.
     */
    public function test_can_update_task(): void
    {
        $task = Task::create([
            'title' => '更新前タスク',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
            'is_completed' => false,
        ]);

        $updatePayload = [
            'title' => '更新後タスク',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
            'is_completed' => true,
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', '更新後タスク')
            ->assertJsonPath('data.priority_type', Task::PRIORITY_NOT_URGENT_IMPORTANT)
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '更新後タスク',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
            'is_completed' => 1,
        ]);
    }

    /**
     * Test deleting a task.
     */
    public function test_can_delete_task(): void
    {
        $task = Task::create([
            'title' => '削除対象タスク',
            'priority_type' => Task::PRIORITY_URGENT_NOT_IMPORTANT,
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
