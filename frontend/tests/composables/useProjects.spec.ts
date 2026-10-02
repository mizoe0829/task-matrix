import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { useProjects } from '../../composables/useProjects';
import type { Project } from '../../types/project';

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

describe('useProjects composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    if (mockState['projects_list']) mockState['projects_list'].value = [];
    if (mockState['current_selected_project']) mockState['current_selected_project'].value = null;
    if (mockState['projects_summary']) mockState['projects_summary'].value = null;
    if (mockState['projects_loading']) mockState['projects_loading'].value = false;
    if (mockState['projects_error']) mockState['projects_error'].value = null;
  });

  const mockProjects: Project[] = [
    {
      id: 1,
      name: 'ECリニューアル',
      description: '次世代EC基盤構築',
      methodology: 'agile',
      status: 'active',
      color: '#6366f1',
      start_date: '2026-10-01',
      end_date: '2026-12-31',
      total_tasks: 10,
      completed_tasks: 4,
      urgent_important_tasks: 2,
      progress_percent: 40,
    },
    {
      id: 2,
      name: '基幹システム移行',
      description: 'ウォーターフォール基盤移行',
      methodology: 'waterfall',
      status: 'active',
      color: '#06b6d4',
      start_date: '2026-10-01',
      end_date: '2027-03-31',
      total_tasks: 20,
      completed_tasks: 10,
      urgent_important_tasks: 1,
      progress_percent: 50,
    },
  ];

  it('fetches projects successfully', async () => {
    mockFetch.mockResolvedValueOnce({ data: mockProjects });

    const { projects, fetchProjects, isLoading, error } = useProjects();
    await fetchProjects();

    expect(mockFetch).toHaveBeenCalledWith('http://localhost:8080/api/projects', {
      headers: { Accept: 'application/json' },
    });
    expect(projects.value).toHaveLength(2);
    expect(isLoading.value).toBe(false);
    expect(error.value).toBeNull();
  });

  it('creates a new project and prepends to list', async () => {
    const newProject: Project = {
      id: 3,
      name: '新規AIプロジェクト',
      description: 'AI機能導入',
      methodology: 'agile',
      status: 'active',
      color: '#10b981',
      start_date: null,
      end_date: null,
      total_tasks: 0,
      completed_tasks: 0,
      progress_percent: 0,
    };
    mockFetch.mockResolvedValueOnce({ data: newProject }); // create response
    mockFetch.mockResolvedValueOnce({ data: {} }); // summary response

    const { projects, createProject } = useProjects();
    projects.value = [...mockProjects];

    const result = await createProject({
      name: '新規AIプロジェクト',
      methodology: 'agile',
    });

    expect(result).toEqual(newProject);
    expect(projects.value).toHaveLength(3);
    expect(projects.value[0].id).toBe(3);
  });

  it('deletes a project from state', async () => {
    mockFetch.mockResolvedValueOnce({ message: 'Deleted' });
    mockFetch.mockResolvedValueOnce({ data: {} }); // summary

    const { projects, deleteProject } = useProjects();
    projects.value = [...mockProjects];

    const success = await deleteProject(1);

    expect(success).toBe(true);
    expect(projects.value.find((p) => p.id === 1)).toBeUndefined();
    expect(projects.value).toHaveLength(1);
  });

  it('selects and deselects current project', () => {
    const { currentProject, selectProject } = useProjects();

    selectProject(mockProjects[0]);
    expect(currentProject.value?.id).toBe(1);

    selectProject(null);
    expect(currentProject.value).toBeNull();
  });
});
