import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAi } from '../../composables/useAi';

vi.stubGlobal('useRuntimeConfig', () => ({
  public: {
    apiBase: 'http://localhost:8080/api',
  },
}));

const mockFetch = vi.fn();
vi.stubGlobal('$fetch', mockFetch);

describe('useAi composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('fetchTriage executes successfully and populates triageResult', async () => {
    const mockTriageResponse = {
      priority_type: 'urgent_important',
      priority_label: 'DO',
      urgency_score: 5,
      importance_score: 5,
      estimated_minutes: 30,
      reason: '緊急トラブル対応のため第1象限と判定しました。',
      action_advice: '最優先で原因特定に着手してください。',
      is_mock: true,
    };
    mockFetch.mockResolvedValueOnce(mockTriageResponse);

    const { fetchTriage, triageResult, isTriaging } = useAi();
    expect(isTriaging.value).toBe(false);

    const promise = fetchTriage('本番DB障害対応', 'サーバー接続不能', '2026-10-03 12:00:00');
    expect(isTriaging.value).toBe(true);

    const result = await promise;
    expect(isTriaging.value).toBe(false);
    expect(result).toEqual(mockTriageResponse);
    expect(triageResult.value?.priority_type).toBe('urgent_important');
    expect(triageResult.value?.priority_label).toBe('DO');
    expect(mockFetch).toHaveBeenCalledWith(
      'http://localhost:8080/api/ai/triage',
      expect.objectContaining({
        method: 'POST',
        body: expect.objectContaining({
          title: '本番DB障害対応',
        }),
      })
    );
  });

  it('fetchTriage fails with validation error if title is empty', async () => {
    const { fetchTriage, error, triageResult } = useAi();

    const result = await fetchTriage('');
    expect(result).toBeNull();
    expect(triageResult.value).toBeNull();
    expect(error.value).toBe('タスク名を入力してください');
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it('fetchBreakdown successfully returns subtasks', async () => {
    const mockBreakdownResponse = {
      breakdown_summary: '3つのタスクに分解しました',
      subtasks: [
        {
          title: 'サブタスク1',
          priority_type: 'plan',
          estimated_minutes: 30,
          description: '要件確認',
        },
      ],
      is_mock: true,
    };
    mockFetch.mockResolvedValueOnce(mockBreakdownResponse);

    const { fetchBreakdown, breakdownResult } = useAi();

    const result = await fetchBreakdown('ソーシャルログイン実装', 'Google認証対応', 'agile');
    expect(result).toEqual(mockBreakdownResponse);
    expect(breakdownResult.value?.subtasks.length).toBe(1);
    expect(mockFetch).toHaveBeenCalledWith(
      'http://localhost:8080/api/ai/breakdown',
      expect.objectContaining({
        method: 'POST',
      })
    );
  });

  it('fetchCoach successfully returns coaching advice', async () => {
    const mockCoachResponse = {
      headline: '第2象限フォーカスが良好です！',
      quadrant_health_score: 85,
      insights: '未来への投資がしっかりできています。',
      recommended_action: 'このペースを維持しましょう。',
      stats: { total: 10, completed: 5, do: 2, plan: 6, delegate: 1, eliminate: 1 },
      is_mock: true,
    };
    mockFetch.mockResolvedValueOnce(mockCoachResponse);

    const { fetchCoach, coachResult } = useAi();

    const result = await fetchCoach(1, 'personal');
    expect(result).toEqual(mockCoachResponse);
    expect(coachResult.value?.quadrant_health_score).toBe(85);
    expect(mockFetch).toHaveBeenCalledWith(
      'http://localhost:8080/api/ai/coach',
      expect.objectContaining({
        method: 'GET',
        params: { task_scope: 'personal', project_id: 1 },
      })
    );
  });
});
