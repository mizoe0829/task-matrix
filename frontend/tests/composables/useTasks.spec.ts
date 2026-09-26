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

describe('useTasks composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset state values
    if (mockState['tasks_list']) mockState['tasks_list'].value = [];
    if (mockState['tasks_loading']) mockState['tasks_loading'].value = false;
    if (mockState['tasks_error']) mockState['tasks_error'].value = null;
  });

  const mockTasks: Task[] = [
    {
      id: 1,
      title: '第1象限タスク',
      description: '緊急重要',
      priority_type: 'urgent_important',
      due_date: '2026-10-01T10:00:00Z',
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 2,
      title: '第2象限タスク',
      description: '計画',
      priority_type: 'not_urgent_important',
      due_date: '2026-10-05T10:00:00Z',
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 3,
      title: '第3象限タスク',
      description: '委託',
      priority_type: 'urgent_not_important',
      due_date: null,
      is_completed: true,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
    {
      id: 4,
      title: '第4象限タスク',
      description: '削減',
      priority_type: 'not_urgent_not_important',
      due_date: null,
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    },
  ];

  it('correctly categorizes tasks into 4 quadrants via tasksByQuadrant', () => {
    const { tasks, tasksByQuadrant } = useTasks();
    tasks.value = [...mockTasks];

    expect(tasksByQuadrant.value.urgent_important).toHaveLength(1);
    expect(tasksByQuadrant.value.urgent_important[0].id).toBe(1);

    expect(tasksByQuadrant.value.not_urgent_important).toHaveLength(1);
    expect(tasksByQuadrant.value.not_urgent_important[0].id).toBe(2);

    expect(tasksByQuadrant.value.urgent_not_important).toHaveLength(1);
    expect(tasksByQuadrant.value.urgent_not_important[0].id).toBe(3);

    expect(tasksByQuadrant.value.not_urgent_not_important).toHaveLength(1);
    expect(tasksByQuadrant.value.not_urgent_not_important[0].id).toBe(4);
  });

  it('fetches tasks successfully from backend API', async () => {
    mockFetch.mockResolvedValueOnce({ data: mockTasks });

    const { tasks, fetchTasks, isLoading, error } = useTasks();
    await fetchTasks();

    expect(mockFetch).toHaveBeenCalledWith('http://localhost:8080/api/tasks', {
      headers: { Accept: 'application/json' },
    });
    expect(tasks.value).toEqual(mockTasks);
    expect(isLoading.value).toBe(false);
    expect(error.value).toBeNull();
  });

  it('creates a new task and prepends it to the tasks state', async () => {
    const newTask: Task = {
      id: 5,
      title: '新しいタスク',
      description: 'テスト作成',
      priority_type: 'urgent_important',
      due_date: null,
      is_completed: false,
      google_event_id: null,
      created_at: null,
      updated_at: null,
    };
    mockFetch.mockResolvedValueOnce({ data: newTask });

    const { tasks, createTask } = useTasks();
    tasks.value = [...mockTasks];

    const result = await createTask({
      title: '新しいタスク',
      priority_type: 'urgent_important',
    });

    expect(result).toEqual(newTask);
    expect(tasks.value).toHaveLength(5);
    expect(tasks.value[0].id).toBe(5);
  });

  it('toggles task completion status', async () => {
    const targetTask = { ...mockTasks[0] };
    const updatedTask = { ...targetTask, is_completed: true };

    mockFetch.mockResolvedValueOnce({ data: updatedTask });

    const { tasks, toggleComplete } = useTasks();
    tasks.value = [targetTask];

    await toggleComplete(targetTask);

    expect(mockFetch).toHaveBeenCalledWith('http://localhost:8080/api/tasks/1', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: { is_completed: true },
    });
    expect(tasks.value[0].is_completed).toBe(true);
  });

  it('changes quadrant of a task', async () => {
    const targetTask = { ...mockTasks[0] }; // urgent_important
    const updatedTask = { ...targetTask, priority_type: 'not_urgent_important' as const };

    mockFetch.mockResolvedValueOnce({ data: updatedTask });

    const { tasks, changeQuadrant } = useTasks();
    tasks.value = [targetTask];

    await changeQuadrant(targetTask, 'not_urgent_important');

    expect(mockFetch).toHaveBeenCalledWith('http://localhost:8080/api/tasks/1', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: { priority_type: 'not_urgent_important' },
    });
    expect(tasks.value[0].priority_type).toBe('not_urgent_important');
  });

  it('deletes a task from state', async () => {
    mockFetch.mockResolvedValueOnce({ message: 'Deleted' });

    const { tasks, deleteTask } = useTasks();
    tasks.value = [...mockTasks];

    const success = await deleteTask(1);

    expect(success).toBe(true);
    expect(tasks.value.find((t) => t.id === 1)).toBeUndefined();
    expect(tasks.value).toHaveLength(3);
  });

  it('handles API error when fetching fails', async () => {
    mockFetch.mockRejectedValueOnce(new Error('Network error'));

    const { fetchTasks, error, isLoading } = useTasks();
    await fetchTasks();

    expect(error.value).toBe('Network error');
    expect(isLoading.value).toBe(false);
  });
});
