import { ref, computed } from 'vue';
import type { Task, CreateTaskPayload, UpdateTaskPayload, PriorityType } from '~/types/task';

export const useTasks = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public?.apiBase || 'http://localhost:8080/api';

  const tasks = useState<Task[]>('tasks_list', () => []);
  const isLoading = useState<boolean>('tasks_loading', () => false);
  const error = useState<string | null>('tasks_error', () => null);

  // Grouped tasks by priority quadrant
  const tasksByQuadrant = computed(() => {
    return {
      urgent_important: tasks.value.filter((t) => t.priority_type === 'urgent_important'),
      not_urgent_important: tasks.value.filter((t) => t.priority_type === 'not_urgent_important'),
      urgent_not_important: tasks.value.filter((t) => t.priority_type === 'urgent_not_important'),
      not_urgent_not_important: tasks.value.filter((t) => t.priority_type === 'not_urgent_not_important'),
    };
  });

  // Fetch all tasks
  const fetchTasks = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task[] }>(`${apiBase}/tasks`, {
        headers: {
          Accept: 'application/json',
        },
      });
      tasks.value = response.data;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'タスクの取得に失敗しました';
      console.error('Failed to fetch tasks:', err);
    } finally {
      isLoading.value = false;
    }
  };

  // Create a new task
  const createTask = async (payload: CreateTaskPayload): Promise<Task | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task }>(`${apiBase}/tasks`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
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

  // Update an existing task
  const updateTask = async (id: number, payload: UpdateTaskPayload): Promise<Task | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Task }>(`${apiBase}/tasks/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
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

  // Toggle complete status
  const toggleComplete = async (task: Task) => {
    return await updateTask(task.id, {
      is_completed: !task.is_completed,
    });
  };

  // Change task quadrant
  const changeQuadrant = async (task: Task, newPriority: PriorityType) => {
    if (task.priority_type === newPriority) return;
    return await updateTask(task.id, {
      priority_type: newPriority,
    });
  };

  // Delete a task
  const deleteTask = async (id: number): Promise<boolean> => {
    isLoading.value = true;
    error.value = null;
    try {
      await $fetch(`${apiBase}/tasks/${id}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
        },
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
    tasksByQuadrant,
    isLoading,
    error,
    fetchTasks,
    createTask,
    updateTask,
    toggleComplete,
    changeQuadrant,
    deleteTask,
  };
};
