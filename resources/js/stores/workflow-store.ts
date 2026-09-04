import { create } from 'zustand';
import type { Workflow, WorkflowRun, WorkflowVersion, WorkflowFilters } from '@/types/workflow';

interface WorkflowState {
  workflows: Workflow[];
  selectedWorkflow: Workflow | null;
  workflowVersions: WorkflowVersion[];
  workflowRuns: WorkflowRun[];
  selectedRun: WorkflowRun | null;
  totalWorkflows: number;
  isLoading: boolean;

  setWorkflows: (workflows: Workflow[]) => void;
  setSelectedWorkflow: (workflow: Workflow | null) => void;
  setWorkflowVersions: (versions: WorkflowVersion[]) => void;
  setWorkflowRuns: (runs: WorkflowRun[]) => void;
  setSelectedRun: (run: WorkflowRun | null) => void;
  setTotalWorkflows: (total: number) => void;
  setLoading: (loading: boolean) => void;
  removeWorkflow: (id: number) => void;
}

export const useWorkflowStore = create<WorkflowState>()((set) => ({
  workflows: [],
  selectedWorkflow: null,
  workflowVersions: [],
  workflowRuns: [],
  selectedRun: null,
  totalWorkflows: 0,
  isLoading: false,

  setWorkflows: (workflows) => set({ workflows }),
  setSelectedWorkflow: (workflow) => set({ selectedWorkflow: workflow }),
  setWorkflowVersions: (versions) => set({ workflowVersions: versions }),
  setWorkflowRuns: (runs) => set({ workflowRuns: runs }),
  setSelectedRun: (run) => set({ selectedRun: run }),
  setTotalWorkflows: (total) => set({ totalWorkflows: total }),
  setLoading: (isLoading) => set({ isLoading }),
  removeWorkflow: (id) =>
    set((state) => ({
      workflows: state.workflows.filter((w) => w.id !== id),
      selectedWorkflow: state.selectedWorkflow?.id === id ? null : state.selectedWorkflow,
    })),
}));
