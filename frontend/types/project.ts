import type { Methodology } from './task';

export type ProjectStatus = 'active' | 'completed' | 'archived';

export interface Project {
  id: number;
  name: string;
  description: string | null;
  methodology: Methodology;
  status: ProjectStatus;
  color: string;
  start_date: string | null;
  end_date: string | null;
  total_tasks: number;
  completed_tasks: number;
  urgent_important_tasks?: number;
  progress_percent: number;
  created_at?: string | null;
}

export interface ProjectSummary {
  total_projects: number;
  active_projects: number;
  completed_projects: number;
  total_tasks: number;
  completed_tasks: number;
  urgent_important_tasks: number;
  overall_progress_percent: number;
}

export interface CreateProjectPayload {
  name: string;
  description?: string | null;
  methodology: Methodology;
  status?: ProjectStatus;
  color?: string;
  start_date?: string | null;
  end_date?: string | null;
}

export interface UpdateProjectPayload {
  name?: string;
  description?: string | null;
  methodology?: Methodology;
  status?: ProjectStatus;
  color?: string;
  start_date?: string | null;
  end_date?: string | null;
}
