import { computed } from 'vue';
import type {
  Project,
  ProjectSummary,
  CreateProjectPayload,
  UpdateProjectPayload,
} from '~/types/project';

export const useProjects = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public?.apiBase || 'http://localhost:8080/api';

  const projects = useState<Project[]>('projects_list', () => []);
  const currentProject = useState<Project | null>('current_selected_project', () => null);
  const projectSummary = useState<ProjectSummary | null>('projects_summary', () => null);
  const isLoading = useState<boolean>('projects_loading', () => false);
  const error = useState<string | null>('projects_error', () => null);

  // Fetch all projects
  const fetchProjects = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Project[] }>(`${apiBase}/projects`, {
        headers: { Accept: 'application/json' },
      });
      projects.value = response.data;

      // If currentProject is selected, refresh its details from the new list
      if (currentProject.value) {
        const found = response.data.find((p) => p.id === currentProject.value?.id);
        if (found) {
          currentProject.value = found;
        }
      }
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'プロジェクトの取得に失敗しました';
      console.error('Failed to fetch projects:', err);
    } finally {
      isLoading.value = false;
    }
  };

  // Fetch summary KPI for global dashboard
  const fetchProjectSummary = async () => {
    try {
      const response = await $fetch<{ data: ProjectSummary }>(`${apiBase}/projects/summary`, {
        headers: { Accept: 'application/json' },
      });
      projectSummary.value = response.data;
    } catch (err: any) {
      console.error('Failed to fetch project summary:', err);
    }
  };

  // Create project
  const createProject = async (payload: CreateProjectPayload): Promise<Project | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Project }>(`${apiBase}/projects`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: payload,
      });
      const newProj = response.data;
      projects.value.unshift(newProj);
      await fetchProjectSummary();
      return newProj;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'プロジェクトの作成に失敗しました';
      console.error('Failed to create project:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  // Update project
  const updateProject = async (id: number, payload: UpdateProjectPayload): Promise<Project | null> => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ data: Project }>(`${apiBase}/projects/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: payload,
      });
      const updated = response.data;
      const index = projects.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        projects.value[index] = { ...projects.value[index], ...updated };
      }
      if (currentProject.value?.id === id) {
        currentProject.value = { ...currentProject.value, ...updated };
      }
      await fetchProjectSummary();
      return updated;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'プロジェクトの更新に失敗しました';
      console.error('Failed to update project:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };

  // Delete project
  const deleteProject = async (id: number): Promise<boolean> => {
    isLoading.value = true;
    error.value = null;
    try {
      await $fetch(`${apiBase}/projects/${id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json' },
      });
      projects.value = projects.value.filter((p) => p.id !== id);
      if (currentProject.value?.id === id) {
        currentProject.value = null;
      }
      await fetchProjectSummary();
      return true;
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'プロジェクトの削除に失敗しました';
      console.error('Failed to delete project:', err);
      return false;
    } finally {
      isLoading.value = false;
    }
  };

  // Select project (for switching to Project Dashboard / filtering tasks)
  const selectProject = (project: Project | null) => {
    currentProject.value = project;
  };

  return {
    projects,
    currentProject,
    projectSummary,
    isLoading,
    error,
    fetchProjects,
    fetchProjectSummary,
    createProject,
    updateProject,
    deleteProject,
    selectProject,
  };
};
