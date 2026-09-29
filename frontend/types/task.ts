export type PriorityType =
  | 'urgent_important'        // 第1象限: 緊急かつ重要 (DO / 必須)
  | 'not_urgent_important'    // 第2象限: 緊急ではないが重要 (PLAN / 価値)
  | 'urgent_not_important'    // 第3象限: 緊急だが重要ではない (DELEGATE / 錯覚)
  | 'not_urgent_not_important'; // 第4象限: 緊急でも重要でもない (ELIMINATE / 無駄)

export type TaskScope = 'team' | 'personal';
export type Methodology = 'agile' | 'waterfall' | 'matrix';
export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done';
export type WaterfallPhase = 'requirement' | 'design' | 'development' | 'testing' | 'release';

export interface Task {
  id: number;
  task_scope: TaskScope;
  methodology: Methodology;
  team_task_id: number | null;
  team_task_title?: string | null;
  assigned_to: string | null;
  title: string;
  description: string | null;
  priority_type: PriorityType;
  status: TaskStatus;
  agile_sprint: string | null;
  agile_story_points: number | null;
  waterfall_phase: WaterfallPhase | null;
  progress_rate: number;
  start_date: string | null;
  due_date: string | null;
  is_completed: boolean;
  google_event_id: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface CreateTaskPayload {
  task_scope?: TaskScope;
  methodology?: Methodology;
  team_task_id?: number | null;
  assigned_to?: string | null;
  title: string;
  description?: string | null;
  priority_type?: PriorityType;
  status?: TaskStatus;
  agile_sprint?: string | null;
  agile_story_points?: number | null;
  waterfall_phase?: WaterfallPhase | null;
  progress_rate?: number;
  start_date?: string | null;
  due_date?: string | null;
  is_completed?: boolean;
}

export interface UpdateTaskPayload {
  task_scope?: TaskScope;
  methodology?: Methodology;
  team_task_id?: number | null;
  assigned_to?: string | null;
  title?: string;
  description?: string | null;
  priority_type?: PriorityType;
  status?: TaskStatus;
  agile_sprint?: string | null;
  agile_story_points?: number | null;
  waterfall_phase?: WaterfallPhase | null;
  progress_rate?: number;
  start_date?: string | null;
  due_date?: string | null;
  is_completed?: boolean;
}

export interface BranchToPersonalPayload {
  priority_type: PriorityType;
  assigned_to?: string | null;
  title?: string;
  description?: string | null;
  due_date?: string | null;
}
