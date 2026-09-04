import apiClient from '@/lib/api/client';
import type { Workflow, WorkflowRun, WorkflowLog, WorkflowVersion, WorkflowFilters } from '@/types/workflow';

function buildQuery(filters?: Record<string, unknown>): string {
  if (!filters) return '';
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value === undefined || value === null) return;
    if (Array.isArray(value)) {
      value.forEach((v) => params.append(`${key}[]`, String(v)));
    } else {
      params.append(key, String(value));
    }
  });
  const qs = params.toString();
  return qs ? `?${qs}` : '';
}

export const workflowApi = {
  async list(filters?: WorkflowFilters) {
    const { data } = await apiClient.get(`/workflows${buildQuery(filters as Record<string, unknown>)}`);
    return data;
  },

  async get(id: number): Promise<Workflow> {
    const { data } = await apiClient.get(`/workflows/${id}`);
    return data.data;
  },

  async create(payload: Record<string, unknown>): Promise<Workflow> {
    const { data } = await apiClient.post('/workflows', payload);
    return data.data;
  },

  async update(id: number, payload: Record<string, unknown>): Promise<Workflow> {
    const { data } = await apiClient.put(`/workflows/${id}`, payload);
    return data.data;
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/workflows/${id}`);
  },

  async enable(id: number): Promise<Workflow> {
    const { data } = await apiClient.post(`/workflows/${id}/enable`);
    return data.data;
  },

  async disable(id: number): Promise<Workflow> {
    const { data } = await apiClient.post(`/workflows/${id}/disable`);
    return data.data;
  },

  async execute(id: number, payload?: Record<string, unknown>, triggerType?: string): Promise<WorkflowRun> {
    const { data } = await apiClient.post(`/workflows/${id}/execute`, { payload, trigger_type: triggerType });
    return data.data;
  },

  async export(id: number): Promise<Record<string, unknown>> {
    const { data } = await apiClient.get(`/workflows/${id}/export`);
    return data.data;
  },

  async import(payload: Record<string, unknown>): Promise<Workflow> {
    const { data } = await apiClient.post('/workflows/import', payload);
    return data.data;
  },

  async versions(id: number): Promise<WorkflowVersion[]> {
    const { data } = await apiClient.get(`/workflows/${id}/versions`);
    return data.data;
  },

  async runs(id: number, perPage = 20) {
    const { data } = await apiClient.get(`/workflows/${id}/runs?per_page=${perPage}`);
    return data;
  },

  async logs(id: number, runId: number, perPage = 50) {
    const { data } = await apiClient.get(`/workflows/${id}/logs?run_id=${runId}&per_page=${perPage}`);
    return data;
  },

  async getRun(runId: number): Promise<WorkflowRun> {
    const { data } = await apiClient.get(`/workflows/runs/${runId}`);
    return data.data;
  },

  async retryRun(runId: number): Promise<WorkflowRun> {
    const { data } = await apiClient.post(`/workflows/runs/${runId}/retry`);
    return data.data;
  },

  async cancelRun(runId: number): Promise<void> {
    await apiClient.post(`/workflows/runs/${runId}/cancel`);
  },
};
