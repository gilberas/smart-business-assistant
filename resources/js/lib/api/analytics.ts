import apiClient from '@/lib/api/client';
import type {
  AnalyticsFilter,
  OverviewData,
  ConversationAnalytics,
  CustomerAnalytics,
  AIAnalytics,
  AgentAnalytics,
  CampaignAnalytics,
  WorkflowAnalytics,
  KnowledgeAnalytics,
} from '@/types/analytics';

function buildQuery(filters?: Record<string, unknown>): string {
  if (!filters || Object.keys(filters).length === 0) return '';
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== null && value !== '') {
      params.append(key, String(value));
    }
  }
  const qs = params.toString();
  return qs ? `?${qs}` : '';
}

export const analyticsApi = {
  async overview(filters?: AnalyticsFilter): Promise<OverviewData> {
    const { data } = await apiClient.get(
      `/analytics/overview${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async conversations(filters?: AnalyticsFilter): Promise<ConversationAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/conversations${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async customers(filters?: AnalyticsFilter): Promise<CustomerAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/customers${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async ai(filters?: AnalyticsFilter): Promise<AIAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/ai${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async agents(filters?: AnalyticsFilter): Promise<AgentAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/agents${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async campaigns(filters?: AnalyticsFilter): Promise<CampaignAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/campaigns${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async workflows(filters?: AnalyticsFilter): Promise<WorkflowAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/workflows${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },

  async knowledge(filters?: AnalyticsFilter): Promise<KnowledgeAnalytics> {
    const { data } = await apiClient.get(
      `/analytics/knowledge${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data.data;
  },
};
