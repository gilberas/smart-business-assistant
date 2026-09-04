import apiClient from '@/lib/api/client';
import type {
  BroadcastCampaign,
  CampaignStats,
  CampaignRecipient,
  WhatsAppTemplate,
  AudiencePreview,
  CampaignFilters,
  TemplateFilters,
  CreateCampaignPayload,
  UpdateCampaignPayload,
  CreateTemplatePayload,
} from '@/types/broadcast';

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

export const broadcastApi = {
  // Audience
  async previewAudience(
    filters: Record<string, unknown>,
    limit = 50,
  ): Promise<AudiencePreview> {
    const { data } = await apiClient.post('/broadcast/audience/preview', {
      filters,
      limit,
    });
    return data.data;
  },

  async countAudience(filters: Record<string, unknown>): Promise<number> {
    const { data } = await apiClient.post('/broadcast/audience/count', {
      filters,
    });
    return data.data.count;
  },

  // Campaigns
  async listCampaigns(
    filters?: CampaignFilters,
  ): Promise<{ data: BroadcastCampaign[]; meta: Record<string, unknown> }> {
    const { data } = await apiClient.get(
      `/broadcast/campaigns${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data;
  },

  async getCampaign(id: number): Promise<{
    campaign: BroadcastCampaign;
    stats: CampaignStats;
  }> {
    const { data } = await apiClient.get(`/broadcast/campaigns/${id}`);
    return { campaign: data.data, stats: data.stats };
  },

  async createCampaign(
    payload: CreateCampaignPayload,
  ): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post('/broadcast/campaigns', payload);
    return data.data;
  },

  async updateCampaign(
    id: number,
    payload: UpdateCampaignPayload,
  ): Promise<BroadcastCampaign> {
    const { data } = await apiClient.put(
      `/broadcast/campaigns/${id}`,
      payload,
    );
    return data.data;
  },

  async deleteCampaign(id: number): Promise<void> {
    await apiClient.delete(`/broadcast/campaigns/${id}`);
  },

  async duplicateCampaign(id: number): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post(
      `/broadcast/campaigns/${id}/duplicate`,
    );
    return data.data;
  },

  async scheduleCampaign(id: number): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post(
      `/broadcast/campaigns/${id}/schedule`,
    );
    return data.data;
  },

  async pauseCampaign(id: number): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post(
      `/broadcast/campaigns/${id}/pause`,
    );
    return data.data;
  },

  async resumeCampaign(id: number): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post(
      `/broadcast/campaigns/${id}/resume`,
    );
    return data.data;
  },

  async cancelCampaign(id: number): Promise<BroadcastCampaign> {
    const { data } = await apiClient.post(
      `/broadcast/campaigns/${id}/cancel`,
    );
    return data.data;
  },

  async archiveCampaign(id: number): Promise<void> {
    await apiClient.post(`/broadcast/campaigns/${id}/archive`);
  },

  async getCampaignStats(id: number): Promise<CampaignStats> {
    const { data } = await apiClient.get(`/broadcast/campaigns/${id}/stats`);
    return data.data;
  },

  async listRecipients(
    id: number,
    filters?: { status?: string; page?: number; per_page?: number },
  ): Promise<{
    data: CampaignRecipient[];
    meta: Record<string, unknown>;
  }> {
    const { data } = await apiClient.get(
      `/broadcast/campaigns/${id}/recipients${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data;
  },

  // Templates
  async listTemplates(
    filters?: TemplateFilters,
  ): Promise<{ data: WhatsAppTemplate[]; meta: Record<string, unknown> }> {
    const { data } = await apiClient.get(
      `/broadcast/templates${buildQuery(filters as Record<string, unknown>)}`,
    );
    return data;
  },

  async getTemplate(id: number): Promise<WhatsAppTemplate> {
    const { data } = await apiClient.get(`/broadcast/templates/${id}`);
    return data.data;
  },

  async createTemplate(
    payload: CreateTemplatePayload,
  ): Promise<WhatsAppTemplate> {
    const { data } = await apiClient.post('/broadcast/templates', payload);
    return data.data;
  },

  async updateTemplate(
    id: number,
    payload: Partial<CreateTemplatePayload>,
  ): Promise<WhatsAppTemplate> {
    const { data } = await apiClient.put(
      `/broadcast/templates/${id}`,
      payload,
    );
    return data.data;
  },

  async deleteTemplate(id: number): Promise<void> {
    await apiClient.delete(`/broadcast/templates/${id}`);
  },

  async syncTemplates(): Promise<{ synced: number }> {
    const { data } = await apiClient.post('/broadcast/templates/sync');
    return data.data;
  },

  async listActiveTemplates(): Promise<WhatsAppTemplate[]> {
    const { data } = await apiClient.get('/broadcast/templates/active');
    return data.data;
  },
};
