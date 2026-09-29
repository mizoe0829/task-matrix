import { ref, computed } from 'vue';
import type {
  Task,
  CreateTaskPayload,
  UpdateTaskPayload,
  BranchToPersonalPayload,
  PriorityType,
  TaskScope,
  Methodology,
  TaskStatus,
} from '~/types/task';

export const useTasks = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public?.apiBase || 'http://localhost:8080/api';

  const tasks = useState<Task[]>('tasks_list', () => []);
  const isLoading = useState<boolean>('tasks_loading', () => false);
  const error = useState<string | null>('tasks_error', () => null);

  // Filtered lists
  const teamTasks = computed(() => tasks.value.filter((t) => t.task_scope === 'team'));
  const personalTasks = computed(() => tasks.value.filter((t) => t.task_scope === 'personal'));

  // Personal Matrix Tasks by quadrant
  const tasksByQuadrant = computed(() => {
    return {
      urgent_important: personalTasks.value.filter((t) => t.priority_type === 'urgent_important'),
      not_urgent_important: personalTasks.value.filter((t) => t.priority_type === 'not_urgent_important'),
      urgent_not_important: personalTasks.value.filter((t) => t.priority_type === 'urgent_not_important'),
      not_urgent_not_important: personalTasks.value.filter((t) => t.priority_type === 'not_urgent_not_important'),
    };
  });

  // Agile Tasks grouped by status (Kanban) or Sprint
  const agileTasks = computed(() => teamTasks.value.filter((t) => t.methodology === 'agile'));
  const agileKanbanColumns = computed(() => {
    return {
      todo: agileTasks.value.filter((t) => t.status === 'todo'),
      in_progress: agileTasks.value.filter((t) => t.status === 'in_progress'),
      review: agileTasks.value.filter((t) => t.status === 'review'),
      done: agileTasks.value.filter((t) => t.status === 'done'),
    };
  });

  // Waterfall Tasks grouped by phase
  const waterfallTasks = computed(() => teamTasks.value.filter((t) => t.methodology === 'waterfall'));
  const waterfallPhases = computed(() => {
    return {
      requirement: waterfallTasks.value.filter((t) => t.waterfall_phase === 'requirement'),
      design: waterfallTasks.value.filter((t) => t.waterfall_phase === 'design'),
      development: waterfallTasks.value.filter((t) => t.waterfall_phase === 'development'),
      testing: waterfallTasks.value.filter((t) => t.waterfall_phase === 'testing'),
      release: waterfallTasks.value.filter((t) => t.waterfall_phase === 'release'),
    };
  });

  // Fetch all tasks
  const fetchTasks = async (scope?: TaskScope) => {
    isLoading.value = true;
    error.value = null;
    try {
      const url = scope ? `${apiBase}/tasks?task_scope=${scope}` : `${apiBase}/tasks`;
      const response = await $fetch<{ data: Task[] }>(url, {
        headers: { Accept: 'application/json' },
      });
      tasks.value = response.data;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'タスクの取得に失敗しました';
      console.error('Failed to fetch tasks:', err);
    } finally {
      isLoading.value = false;
    }
  };

  // Create task
  const createTask = async (payload: CreateTaskPayload): Promise<Task | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task }>(`${apiBase}/tasks`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: payload,
      });
      const newTask = response.data;
      tasks.value.unshift(newTask);
      return newTask;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'タスクの作成に失敗しました';
      console.error('Failed to create task:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  // Update task
  const updateTask = async (id: number, payload: UpdateTaskPayload): Promise<Task | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task }>(`${apiBase}/tasks/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: payload,
      });
      const updated = response.data;
      const index = tasks.value.findIndex((t) => t.id === id);
      if (index !== -1) {
        tasks.value[index] = updated;
      }
      return updated;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'タスクの更新に失敗しました';
      console.error('Failed to update task:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  // Branch team task into personal task
  const branchToPersonal = async (teamTaskId: number, payload: BranchToPersonalPayload): Promise<Task | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task }>(`${apiBase}/tasks/${teamTaskId}/branch-to-personal`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: payload,
      });
      const personalTask = response.data;
      tasks.value.unshift(personalTask);
      return personalTask;
    } catch (err: any) {
      error.value = err.data?.message || err.message || '個人タスクへの取り込みに失敗しました';
      console.error('Failed to branch task:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  // Toggle complete
  const toggleComplete = async (task: Task) => {
    const nextStatus: TaskStatus = !task.is_completed ? 'done' : 'todo';
    return await updateTask(task.id, {
      is_completed: !task.is_completed,
      status: nextStatus,
    });
  };

  // Change quadrant
  const changeQuadrant = async (task: Task, newPriority: PriorityType) => {
    if (task.priority_type === newPriority) return;
    return await updateTask(task.id, { priority_type: newPriority });
  };

  // Update status (for Kanban drag & drop or click)
  const updateStatus = async (task: Task, newStatus: TaskStatus) => {
    if (task.status === newStatus) return;
    return await updateTask(task.id, {
      status: newStatus,
      is_completed: newStatus === 'done',
    });
  };

  // Delete task
  const deleteTask = async (id: number): Promise<boolean> => {
    isLoading.value = true;
    error.value = null;
    try {
      await $fetch(`${apiBase}/tasks/${id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json' },
      });
      tasks.value = tasks.value.filter((t) => t.id !== id);
      return true;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'タスクの削除に失敗しました';
      console.error('Failed to delete task:', err);
      return false;
    } finally {
      isLoading.value = false;
    }
  };

  return {
    tasks,
    teamTasks,
    personalTasks,
    tasksByQuadrant,
    agileTasks,
    agileKanbanColumns,
    waterfallTasks,
    waterfallPhases,
    isLoading,
    error,
    fetchTasks,
    createTask,
    updateTask,
    branchToPersonal,
    toggleComplete,
    changeQuadrant,
    updateStatus,
    deleteTask,
  };
};
