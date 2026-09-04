import { create } from 'zustand';
import type {
  BroadcastCampaign,
  CampaignStats,
  CampaignRecipient,
  WhatsAppTemplate,
} from '@/types/broadcast';

interface BroadcastState {
  campaigns: BroadcastCampaign[];
  selectedCampaign: BroadcastCampaign | null;
  campaignStats: CampaignStats | null;
  recipients: CampaignRecipient[];
  totalCampaigns: number;

  templates: WhatsAppTemplate[];
  selectedTemplate: WhatsAppTemplate | null;
  totalTemplates: number;

  isLoading: boolean;

  setCampaigns: (campaigns: BroadcastCampaign[]) => void;
  setSelectedCampaign: (campaign: BroadcastCampaign | null) => void;
  setCampaignStats: (stats: CampaignStats | null) => void;
  setRecipients: (recipients: CampaignRecipient[]) => void;
  setTotalCampaigns: (total: number) => void;

  setTemplates: (templates: WhatsAppTemplate[]) => void;
  setSelectedTemplate: (template: WhatsAppTemplate | null) => void;
  setTotalTemplates: (total: number) => void;

  setLoading: (loading: boolean) => void;

  removeCampaign: (id: number) => void;
  removeTemplate: (id: number) => void;
}

export const useBroadcastStore = create<BroadcastState>()((set) => ({
  campaigns: [],
  selectedCampaign: null,
  campaignStats: null,
  recipients: [],
  totalCampaigns: 0,

  templates: [],
  selectedTemplate: null,
  totalTemplates: 0,

  isLoading: false,

  setCampaigns: (campaigns) => set({ campaigns }),
  setSelectedCampaign: (campaign) => set({ selectedCampaign: campaign }),
  setCampaignStats: (stats) => set({ campaignStats: stats }),
  setRecipients: (recipients) => set({ recipients }),
  setTotalCampaigns: (total) => set({ totalCampaigns: total }),

  setTemplates: (templates) => set({ templates }),
  setSelectedTemplate: (template) => set({ selectedTemplate: template }),
  setTotalTemplates: (total) => set({ totalTemplates: total }),

  setLoading: (loading) => set({ isLoading: loading }),

  removeCampaign: (id) =>
    set((state) => ({
      campaigns: state.campaigns.filter((c) => c.id !== id),
      selectedCampaign:
        state.selectedCampaign?.id === id ? null : state.selectedCampaign,
    })),

  removeTemplate: (id) =>
    set((state) => ({
      templates: state.templates.filter((t) => t.id !== id),
      selectedTemplate:
        state.selectedTemplate?.id === id ? null : state.selectedTemplate,
    })),
}));
