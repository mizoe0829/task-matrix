import { ref } from 'vue';
import type { AiTriageResult, AiBreakdownResult, AiCoachResult } from '~/types/ai';

export const useAi = () => {
  const isTriaging = ref(false);
  const isBreakingDown = ref(false);
  const isCoaching = ref(false);
  const triageResult = ref<AiTriageResult | null>(null);
  const breakdownResult = ref<AiBreakdownResult | null>(null);
  const coachResult = ref<AiCoachResult | null>(null);
  const error = ref<string | null>(null);

  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase || 'http://localhost:8080/api';

  const fetchTriage = async (
    title: string,
    description?: string,
    dueDate?: string,
    scope?: string
  ): Promise<AiTriageResult | null> => {
    if (!title.trim()) {
      error.value = 'タスク名を入力してください';
      return null;
    }

    isTriaging.value = true;
    error.value = null;

    try {
      const response = await $fetch<AiTriageResult>(`${apiBase}/ai/triage`, {
        method: 'POST',
        body: {
          title,
          description: description || undefined,
          due_date: dueDate || undefined,
          scope: scope || 'personal',
        },
      });

      triageResult.value = response;
      return response;
    } catch (err: any) {
      console.error('AI Triage error:', err);
      error.value = err?.data?.message || 'AIトリアージの実行に失敗しました';
      return null;
    } finally {
      isTriaging.value = false;
    }
  };

  const fetchBreakdown = async (
    title: string,
    description?: string,
    methodology?: string
  ): Promise<AiBreakdownResult | null> => {
    if (!title.trim()) {
      error.value = '親タスク名を入力してください';
      return null;
    }

    isBreakingDown.value = true;
    error.value = null;

    try {
      const response = await $fetch<AiBreakdownResult>(`${apiBase}/ai/breakdown`, {
        method: 'POST',
        body: {
          title,
          description: description || undefined,
          methodology: methodology || 'agile',
        },
      });

      breakdownResult.value = response;
      return response;
    } catch (err: any) {
      console.error('AI Breakdown error:', err);
      error.value = err?.data?.message || 'タスク自動分解に失敗しました';
      return null;
    } finally {
      isBreakingDown.value = false;
    }
  };

  const fetchCoach = async (
    projectId?: number | null,
    scope: string = 'personal'
  ): Promise<AiCoachResult | null> => {
    isCoaching.value = true;
    error.value = null;

    try {
      const params: Record<string, any> = { task_scope: scope };
      if (projectId) {
        params.project_id = projectId;
      }

      const response = await $fetch<AiCoachResult>(`${apiBase}/ai/coach`, {
        method: 'GET',
        params,
      });

      coachResult.value = response;
      return response;
    } catch (err: any) {
      console.error('AI Coach error:', err);
      error.value = err?.data?.message || 'AIコーチングの取得に失敗しました';
      return null;
    } finally {
      isCoaching.value = false;
    }
  };

  const clearTriage = () => {
    triageResult.value = null;
    error.value = null;
  };

  const clearBreakdown = () => {
    breakdownResult.value = null;
    error.value = null;
  };

  return {
    isTriaging,
    isBreakingDown,
    isCoaching,
    triageResult,
    breakdownResult,
    coachResult,
    error,
    fetchTriage,
    fetchBreakdown,
    fetchCoach,
    clearTriage,
    clearBreakdown,
  };
};
