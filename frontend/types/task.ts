export type PriorityType =
  | 'urgent_important'        // 第1象限: 緊急かつ重要 (DO / 必須)
  | 'not_urgent_important'    // 第2象限: 緊急ではないが重要 (PLAN / 価値)
  | 'urgent_not_important'    // 第3象限: 緊急だが重要ではない (DELEGATE / 錯覚)
  | 'not_urgent_not_important'; // 第4象限: 緊急でも重要でもない (ELIMINATE / 無駄)

export interface Task {
  id: number;
  title: string;
  description: string | null;
  priority_type: PriorityType;
  due_date: string | null;
  is_completed: boolean;
  google_event_id: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface CreateTaskPayload {
  title: string;
  description?: string | null;
  priority_type: PriorityType;
  due_date?: string | null;
  is_completed?: boolean;
  google_event_id?: string | null;
}

export interface UpdateTaskPayload {
  title?: string;
  description?: string | null;
  priority_type?: PriorityType;
  due_date?: string | null;
  is_completed?: boolean;
  google_event_id?: string | null;
}

export interface QuadrantConfig {
  type: PriorityType;
  title: string;
  subtitle: string;
  badgeText: string;
  bgColor: string;
  borderColor: string;
  headerBg: string;
  textColor: string;
  accentColor: string;
}
