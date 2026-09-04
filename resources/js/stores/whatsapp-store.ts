import { create } from 'zustand';
import type {
    WhatsAppAccount,
    WhatsAppMessage,
    WhatsAppMedia,
    WebhookEvent,
    AccountDiagnostics,
    WebhookHealth,
    MessageStats,
} from '@/types/whatsapp';

type WhatsAppState = {
    account: WhatsAppAccount | null;
    messages: WhatsAppMessage[];
    selectedMessage: WhatsAppMessage | null;
    media: WhatsAppMedia[];
    webhookEvents: WebhookEvent[];
    diagnostics: AccountDiagnostics | null;
    webhookHealth: WebhookHealth | null;
    messageStats: MessageStats | null;
    isLoading: boolean;
    error: string | null;
};

type WhatsAppActions = {
    setAccount: (account: WhatsAppAccount | null) => void;
    setMessages: (messages: WhatsAppMessage[]) => void;
    setSelectedMessage: (message: WhatsAppMessage | null) => void;
    setMedia: (media: WhatsAppMedia[]) => void;
    setWebhookEvents: (events: WebhookEvent[]) => void;
    setDiagnostics: (diagnostics: AccountDiagnostics | null) => void;
    setWebhookHealth: (health: WebhookHealth | null) => void;
    setMessageStats: (stats: MessageStats | null) => void;
    setLoading: (loading: boolean) => void;
    setError: (error: string | null) => void;
    reset: () => void;
};

const initialState: WhatsAppState = {
    account: null,
    messages: [],
    selectedMessage: null,
    media: [],
    webhookEvents: [],
    diagnostics: null,
    webhookHealth: null,
    messageStats: null,
    isLoading: false,
    error: null,
};

export const useWhatsAppStore = create<WhatsAppState & WhatsAppActions>((set) => ({
    ...initialState,
    setAccount: (account) => set({ account }),
    setMessages: (messages) => set({ messages }),
    setSelectedMessage: (message) => set({ selectedMessage: message }),
    setMedia: (media) => set({ media }),
    setWebhookEvents: (events) => set({ webhookEvents: events }),
    setDiagnostics: (diagnostics) => set({ diagnostics }),
    setWebhookHealth: (health) => set({ webhookHealth: health }),
    setMessageStats: (stats) => set({ messageStats: stats }),
    setLoading: (isLoading) => set({ isLoading }),
    setError: (error) => set({ error }),
    reset: () => set(initialState),
}));
