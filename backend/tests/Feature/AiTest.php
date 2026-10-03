<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AI トリアージの正常系テスト
     */
    public function test_ai_triage_returns_valid_structure(): void
    {
        $response = $this->postJson('/api/ai/triage', [
            'title' => '本番DB接続エラーの緊急対応',
            'description' => 'ユーザーログイン時に500エラーが多発しているため至急調査',
            'due_date' => now()->addHours(2)->toIso8601String(),
            'scope' => 'personal',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'priority_type',
                'urgency_score',
                'importance_score',
                'estimated_minutes',
                'reason',
                'action_advice',
                'is_mock',
            ]);

        $data = $response->json();
        // 緊急キーワードを含むため 'urgent_important' (第1象限) と判定されることを期待
        $this->assertEquals(Task::PRIORITY_URGENT_IMPORTANT, $data['priority_type']);
        $this->assertEquals('DO', $data['priority_label']);
        $this->assertGreaterThanOrEqual(4, $data['urgency_score']);
    }

    /**
     * AI トリアージのバリデーションテスト
     */
    public function test_ai_triage_requires_title(): void
    {
        $response = $this->postJson('/api/ai/triage', [
            'description' => 'タイトルがないリクエスト',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * AI タスク自動分解の正常系テスト
     */
    public function test_ai_breakdown_returns_subtasks(): void
    {
        $response = $this->postJson('/api/ai/breakdown', [
            'title' => 'OAuth2.0ソーシャルログイン機能の実装',
            'description' => 'GoogleおよびGitHubアカウントによるシングルサインオン対応',
            'methodology' => 'agile',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'breakdown_summary',
                'subtasks' => [
                    '*' => [
                        'title',
                        'priority_type',
                        'priority_label',
                        'estimated_minutes',
                        'description',
                    ]
                ],
                'is_mock',
            ]);

        $data = $response->json();
        $this->assertNotEmpty($data['subtasks']);
    }

    /**
     * AI コーチングの正常系テスト
     */
    public function test_ai_coach_returns_insights_based_on_tasks(): void
    {
        // 第1象限と第2象限のタスクを作成
        Task::create([
            'title' => '障害対応',
            'priority_type' => Task::PRIORITY_URGENT_IMPORTANT,
            'task_scope' => 'personal',
        ]);
        Task::create([
            'title' => 'アーキテクチャ設計書の作成',
            'priority_type' => Task::PRIORITY_NOT_URGENT_IMPORTANT,
            'task_scope' => 'personal',
        ]);

        $response = $this->getJson('/api/ai/coach?task_scope=personal');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'headline',
                'quadrant_health_score',
                'insights',
                'recommended_action',
                'stats' => [
                    'total',
                    'completed',
                    'do',
                    'plan',
                    'delegate',
                    'eliminate',
                ],
                'is_mock',
            ]);

        $data = $response->json();
        $this->assertEquals(2, $data['stats']['total']);
        $this->assertEquals(1, $data['stats']['do']);
        $this->assertEquals(1, $data['stats']['plan']);
    }
}
