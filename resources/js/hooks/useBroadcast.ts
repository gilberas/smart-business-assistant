import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { broadcastApi } from '@/lib/api/broadcast';
import { useBroadcastStore } from '@/stores/broadcast-store';
import type {
  CampaignFilters,
  TemplateFilters,
  CreateCampaignPayload,
  UpdateCampaignPayload,
  CreateTemplatePayload,
} from '@/types/broadcast';
import { toast } from 'sonner';

export function useCampaigns(filters?: CampaignFilters) {
  const setCampaigns = useBroadcastStore((s) => s.setCampaigns);
  const setTotalCampaigns = useBroadcastStore((s) => s.setTotalCampaigns);
  const setLoading = useBroadcastStore((s) => s.setLoading);

  return useQuery({
    queryKey: ['broadcast-campaigns', filters],
    queryFn: async () => {
      setLoading(true);
      try {
        const result = await broadcastApi.listCampaigns(filters);
        setCampaigns(result.data);
        setTotalCampaigns(result.meta.total as number);
        return result;
      } finally {
        setLoading(false);
      }
    },
  });
}

export function useCampaign(id: number) {
  const setSelectedCampaign = useBroadcastStore((s) => s.setSelectedCampaign);
  const setCampaignStats = useBroadcastStore((s) => s.setCampaignStats);

  return useQuery({
    queryKey: ['broadcast-campaign', id],
    queryFn: async () => {
      const result = await broadcastApi.getCampaign(id);
      setSelectedCampaign(result.campaign);
      setCampaignStats(result.stats);
      return result;
    },
    enabled: !!id,
  });
}

export function useRecipients(
  campaignId: number,
  filters?: { status?: string; page?: number; per_page?: number },
) {
  const setRecipients = useBroadcastStore((s) => s.setRecipients);

  return useQuery({
    queryKey: ['broadcast-recipients', campaignId, filters],
    queryFn: async () => {
      const result = await broadcastApi.listRecipients(campaignId, filters);
      setRecipients(result.data);
      return result;
    },
    enabled: !!campaignId,
  });
}

export function useCreateCampaign() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateCampaignPayload) =>
      broadcastApi.createCampaign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaigns'] });
      toast.success('Campaign created successfully');
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to create campaign');
    },
  });
}

export function useUpdateCampaign(id: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdateCampaignPayload) =>
      broadcastApi.updateCampaign(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaigns'] });
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaign', id] });
      toast.success('Campaign updated successfully');
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to update campaign');
    },
  });
}

export function useDeleteCampaign() {
  const queryClient = useQueryClient();
  const removeCampaign = useBroadcastStore((s) => s.removeCampaign);

  return useMutation({
    mutationFn: (id: number) => broadcastApi.deleteCampaign(id),
    onSuccess: (_data, id) => {
      removeCampaign(id);
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaigns'] });
      toast.success('Campaign deleted');
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to delete campaign');
    },
  });
}

export function useCampaignAction(action: 'schedule' | 'pause' | 'resume' | 'cancel' | 'duplicate') {
  const queryClient = useQueryClient();

  const actionMap = {
    schedule: broadcastApi.scheduleCampaign,
    pause: broadcastApi.pauseCampaign,
    resume: broadcastApi.resumeCampaign,
    cancel: broadcastApi.cancelCampaign,
    duplicate: broadcastApi.duplicateCampaign,
  };

  const labelMap: Record<string, string> = {
    schedule: 'scheduled',
    pause: 'paused',
    resume: 'resumed',
    cancel: 'cancelled',
    duplicate: 'duplicated',
  };

  return useMutation({
    mutationFn: (id: number) => actionMap[action](id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaigns'] });
      queryClient.invalidateQueries({ queryKey: ['broadcast-campaign'] });
      toast.success(`Campaign ${labelMap[action]} successfully`);
    },
    onError: (err: Error) => {
      toast.error(err.message || `Failed to ${action} campaign`);
    },
  });
}

export function useTemplates(filters?: TemplateFilters) {
  const setTemplates = useBroadcastStore((s) => s.setTemplates);
  const setTotalTemplates = useBroadcastStore((s) => s.setTotalTemplates);
  const setLoading = useBroadcastStore((s) => s.setLoading);

  return useQuery({
    queryKey: ['broadcast-templates', filters],
    queryFn: async () => {
      setLoading(true);
      try {
        const result = await broadcastApi.listTemplates(filters);
        setTemplates(result.data);
        setTotalTemplates(result.meta.total as number);
        return result;
      } finally {
        setLoading(false);
      }
    },
  });
}

export function useActiveTemplates() {
  return useQuery({
    queryKey: ['broadcast-templates', 'active'],
    queryFn: () => broadcastApi.listActiveTemplates(),
  });
}

export function useCreateTemplate() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateTemplatePayload) =>
      broadcastApi.createTemplate(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['broadcast-templates'] });
      toast.success('Template created successfully');
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to create template');
    },
  });
}

export function useDeleteTemplate() {
  const queryClient = useQueryClient();
  const removeTemplate = useBroadcastStore((s) => s.removeTemplate);

  return useMutation({
    mutationFn: (id: number) => broadcastApi.deleteTemplate(id),
    onSuccess: (_data, id) => {
      removeTemplate(id);
      queryClient.invalidateQueries({ queryKey: ['broadcast-templates'] });
      toast.success('Template deleted');
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to delete template');
    },
  });
}

export function useSyncTemplates() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => broadcastApi.syncTemplates(),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['broadcast-templates'] });
      toast.success(`Synced ${data.synced} template(s) from Meta`);
    },
    onError: (err: Error) => {
      toast.error(err.message || 'Failed to sync templates');
    },
  });
}

export function useAudiencePreview() {
  return useMutation({
    mutationFn: ({
      filters,
      limit,
    }: {
      filters: Record<string, unknown>;
      limit?: number;
    }) => broadcastApi.previewAudience(filters, limit),
  });
}

export function useAudienceCount() {
  return useMutation({
    mutationFn: (filters: Record<string, unknown>) =>
      broadcastApi.countAudience(filters),
  });
}
