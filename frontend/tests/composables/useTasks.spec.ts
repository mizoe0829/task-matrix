import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { useTasks } from '../../composables/useTasks';
import type { Task } from '../../types/task';

// Mock Nuxt 3 auto-imported helpers
const mockState: Record<string, any> = {};

vi.stubGlobal('useRuntimeConfig', () => ({
  public: {
    apiBase: 'http://localhost:8080/api',
  },
}));

vi.stubGlobal('useState', (key: string, init: () => any) => {
  if (!mockState[key]) {
    mockState[key] = ref(init());
  }
  return mockState[key];
});

const mockFetch = vi.fn();
vi.stubGlobal('$fetch', mockFetch);

describe('useTasks composable (Team & Personal flow)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    if (mockState['tasks_list']) mockState['tasks_list'].value = [];
    if (mockState['tasks_loading']) mockState['tasks_loading'].value = false;
    if (mockState['tasks_error']) mockState['tasks_error'].value = null;
  });

  const mockTasks: Task[] = [
    {
      id: 1,
      task_scope: 'personal',
      methodology: 'matrix',
      team_task_id: null,
      assigned_to: '自分',
      title: '第1象限個人タスク',
      description: '緊急重要',
      priority_type: 'urgent_important',
      status: 'todo',
      agile_sprint: null,
      agile_story_points: null,
      waterfall_phase: null,
      progress_rate: 0,
      start_date: null,
      due_date: '2026-10-01T10:00:00Z',
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 2,
      task_scope: 'personal',
      methodology: 'matrix',
      team_task_id: null,
      assigned_to: '自分',
      title: '第2象限個人タスク',
      description: '計画',
      priority_type: 'not_urgent_important',
      status: 'todo',
      agile_sprint: null,
      agile_story_points: null,
      waterfall_phase: null,
      progress_rate: 0,
      start_date: null,
      due_date: '2026-10-05T10:00:00Z',
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 3,
      task_scope: 'team',
      methodology: 'agile',
      team_task_id: null,
      assigned_to: '佐藤',
      title: 'アジャイルチームタスク',
      description: 'スプリント開発',
      priority_type: 'urgent_important',
      status: 'in_progress',
      agile_sprint: 'Sprint 1',
      agile_story_points: 5,
      waterfall_phase: null,
      progress_rate: 30,
      start_date: null,
      due_date: null,
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 4,
      task_scope: 'team',
      methodology: 'waterfall',
      team_task_id: null,
      assigned_to: '鈴木',
      title: '要件定義タスク',
      description: '仕様ヒアリング',
      priority_type: 'not_urgent_important',
      status: 'todo',
      agile_sprint: null,
      agile_story_points: null,
      waterfall_phase: 'requirement',
      progress_rate: 50,
      start_date: null,
      due_date: null,
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
  ];

  it('separates teamTasks and personalTasks correctly', () => {
    const { tasks, teamTasks, personalTasks } = useTasks();
    tasks.value = [...mockTasks];

    expect(personalTasks.value).toHaveLength(2);
    expect(teamTasks.value).toHaveLength(2);
  });

  it('groups personal tasks into 4 quadrants', () => {
    const { tasks, tasksByQuadrant } = useTasks();
    tasks.value = [...mockTasks];

    expect(tasksByQuadrant.value.urgent_important).toHaveLength(1);
    expect(tasksByQuadrant.value.urgent_important[0].id).toBe(1);

    expect(tasksByQuadrant.value.not_urgent_important).toHaveLength(1);
    expect(tasksByQuadrant.value.not_urgent_important[0].id).toBe(2);
  });

  it('categorizes agile tasks by status in kanban columns', () => {
    const { tasks, agileKanbanColumns } = useTasks();
    tasks.value = [...mockTasks];

    expect(agileKanbanColumns.value.in_progress).toHaveLength(1);
    expect(agileKanbanColumns.value.in_progress[0].id).toBe(3);
  });

  it('categorizes waterfall tasks by phase', () => {
    const { tasks, waterfallPhases } = useTasks();
    tasks.value = [...mockTasks];

    expect(waterfallPhases.value.requirement).toHaveLength(1);
    expect(waterfallPhases.value.requirement[0].id).toBe(4);
  });

  it('branches team task into personal task via branchToPersonal', async () => {
    const branchedPersonalTask: Task = {
      id: 5,
      task_scope: 'personal',
      methodology: 'matrix',
      team_task_id: 3,
      team_task_title: 'アジャイルチームタスク',
      assigned_to: '自分',
      title: 'アジャイルチームタスク (私の分担)',
      description: 'スプリント開発の担当モジュール',
      priority_type: 'urgent_important',
      status: 'todo',
      agile_sprint: null,
      agile_story_points: null,
      waterfall_phase: null,
      progress_rate: 0,
      start_date: null,
      due_date: null,
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    };
    mockFetch.mockResolvedValueOnce({ data: branchedPersonalTask });

    const { tasks, branchToPersonal } = useTasks();
    tasks.value = [...mockTasks];

    const result = await branchToPersonal(3, {
      priority_type: 'urgent_important',
      title: 'アジャイルチームタスク (私の分担)',
    });

    expect(mockFetch).toHaveBeenCalledWith('http://localhost:8080/api/tasks/3/branch-to-personal', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: {
        priority_type: 'urgent_important',
        title: 'アジャイルチームタスク (私の分担)',
      },
    });
    expect(result).toEqual(branchedPersonalTask);
    expect(tasks.value).toHaveLength(5);
    expect(tasks.value[0].team_task_id).toBe(3);
  });
});
