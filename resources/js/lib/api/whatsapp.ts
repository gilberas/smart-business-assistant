import apiClient from '@/lib/api/client';
import type { PaginatedResponse } from '@/types/common';
import type {
    WhatsAppAccount,
    WhatsAppMessage,
    WhatsAppMessageList,
    WhatsAppMedia,
    WebhookEvent,
    AccountDiagnostics,
    WebhookHealth,
    SendMessagePayload,
    ConnectAccountPayload,
    MessageStats,
} from '@/types/whatsapp';

function buildQuery(filters?: Record<string, unknown>): string {
    if (!filters) {
        return '';
    }

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`${key}[]`, String(v)));
        } else {
            params.append(key, String(value));
        }
    });
    const qs = params.toString();

    return qs ? `?${qs}` : '';
}

export const whatsappApi = {
    // Account management
    async getAccount(): Promise<WhatsAppAccount | null> {
        const { data } = await apiClient.get('/whatsapp/account');

        return data.data;
    },

    async connect(payload: ConnectAccountPayload): Promise<WhatsAppAccount> {
        const { data } = await apiClient.post('/whatsapp/account/connect', payload);

        return data.data;
    },

    async disconnect(): Promise<void> {
        await apiClient.post('/whatsapp/account/disconnect');
    },

    async verify(payload: {
        access_token: string;
        waba_id?: string;
        phone_number_id?: string;
    }): Promise<{ valid: boolean; waba?: Record<string, unknown>; phone_numbers?: unknown[]; note?: string }> {
        const { data } = await apiClient.post('/whatsapp/account/verify', payload);

        return data.data;
    },

    async reconnect(payload: { access_token: string }): Promise<WhatsAppAccount> {
        const { data } = await apiClient.post('/whatsapp/account/reconnect', payload);

        return data.data;
    },

    async sync(): Promise<{ phone_numbers: unknown[]; synced_at: string }> {
        const { data } = await apiClient.post('/whatsapp/account/sync');

        return data.data;
    },

    async getStatus(): Promise<{ connected: boolean; status: string }> {
        const { data } = await apiClient.get('/whatsapp/account/status');

        return data.data;
    },

    async getInfo(): Promise<{ account: WhatsAppAccount; business_profile: unknown }> {
        const { data } = await apiClient.get('/whatsapp/account/info');

        return data.data;
    },

    async getDiagnostics(): Promise<AccountDiagnostics> {
        const { data } = await apiClient.get('/whatsapp/diagnostics');

        return data.data;
    },

    // Messages
    async listMessages(
        filters?: Record<string, unknown>,
    ): Promise<PaginatedResponse<WhatsAppMessageList>> {
        const { data } = await apiClient.get(`/whatsapp/messages${buildQuery(filters)}`);

        return data;
    },

    async getMessage(id: number): Promise<WhatsAppMessage> {
        const { data } = await apiClient.get(`/whatsapp/messages/${id}`);

        return data.data;
    },

    async sendMessage(payload: SendMessagePayload): Promise<WhatsAppMessage> {
        const { data } = await apiClient.post('/whatsapp/messages/send', payload);

        return data.data;
    },

    async getConversation(phoneNumber: string, limit?: number): Promise<WhatsAppMessage[]> {
        const { data } = await apiClient.get(
            `/whatsapp/messages/conversation/${phoneNumber}${limit ? `?limit=${limit}` : ''}`,
        );

        return data.data;
    },

    async getMessageStats(): Promise<MessageStats> {
        const { data } = await apiClient.get('/whatsapp/messages/stats');

        return data.data;
    },

    // Media
    async listMedia(filters?: Record<string, unknown>): Promise<PaginatedResponse<WhatsAppMedia>> {
        const { data } = await apiClient.get(`/whatsapp/media${buildQuery(filters)}`);

        return data;
    },

    async getMedia(id: number): Promise<WhatsAppMedia> {
        const { data } = await apiClient.get(`/whatsapp/media/${id}`);

        return data.data;
    },

    async uploadMedia(file: File, type?: string): Promise<WhatsAppMedia> {
        const formData = new FormData();
        formData.append('file', file);

        if (type) {
            formData.append('type', type);
        }

        const { data } = await apiClient.post('/whatsapp/media', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data.data;
    },

    async downloadMedia(id: number): Promise<Blob> {
        const { data } = await apiClient.get(`/whatsapp/media/${id}/download`, {
            responseType: 'blob',
        });

        return data;
    },

    async deleteMedia(id: number): Promise<void> {
        await apiClient.delete(`/whatsapp/media/${id}`);
    },

    // Webhook
    async getWebhookHealth(): Promise<WebhookHealth> {
        const { data } = await apiClient.get('/whatsapp/webhook/health');

        return data.data;
    },

    async listWebhookEvents(
        filters?: Record<string, unknown>,
    ): Promise<PaginatedResponse<WebhookEvent>> {
        const { data } = await apiClient.get(`/whatsapp/webhook/events${buildQuery(filters)}`);

        return data;
    },
};
