import type { PriorityType } from './task';

export type PriorityLabel = 'DO' | 'PLAN' | 'DELEGATE' | 'ELIMINATE';

export interface AiTriageResult {
  priority_type: PriorityType;
  priority_label: PriorityLabel;
  urgency_score: number;       // 1 - 5
  importance_score: number;    // 1 - 5
  estimated_minutes: number;
  reason: string;
  action_advice: string;
  is_mock?: boolean;
}

export interface AiSubtask {
  title: string;
  priority_type: PriorityType;
  priority_label?: PriorityLabel;
  estimated_minutes: number;
  description: string;
}

export interface AiBreakdownResult {
  breakdown_summary: string;
  subtasks: AiSubtask[];
  is_mock?: boolean;
}

export interface AiCoachStats {
  total: number;
  completed: number;
  do: number;
  plan: number;
  delegate: number;
  eliminate: number;
}

export interface AiCoachResult {
  headline: string;
  quadrant_health_score: number;
  insights: string;
  recommended_action: string;
  stats?: AiCoachStats;
  is_mock?: boolean;
}
