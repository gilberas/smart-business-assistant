import apiClient from '@/lib/api/client';
import type { PaginatedResponse } from '@/types/common';
import type { InboxConversation, InboxConversationListResponse, InboxSuggestionPackage, InboxWorkspace } from '@/types/inbox';

export type InboxFilters = {
    status?: string;
    priority?: string;
    channel?: string;
    source?: string;
    assigned_to?: number | string;
    unassigned?: boolean;
    search?: string;
    label_id?: number | string;
    customer_id?: number | string;
    active?: boolean;
    sort?: string;
    direction?: string;
    per_page?: number;
};

export const inboxApi = {
    async list(filters: InboxFilters = {}): Promise<InboxConversationListResponse> {
        const { data } = await apiClient.get('/inbox/conversations', { params: filters });
        return data;
    },

    async show(conversationId: number): Promise<{ data: InboxWorkspace }> {
        const { data } = await apiClient.get(`/inbox/conversations/${conversationId}`);
        return data;
    },

    async assign(conversationId: number, userId: number, reason?: string) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/assign`, { user_id: userId, reason });
        return data;
    },

    async takeover(conversationId: number, reason?: string) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/takeover`, { reason });
        return data;
    },

    async release(conversationId: number, reason?: string) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/release`, { reason });
        return data;
    },

    async updateStatus(conversationId: number, status: string) {
        const { data } = await apiClient.patch(`/inbox/conversations/${conversationId}/status`, { status });
        return data;
    },

    async togglePin(conversationId: number, isPinned: boolean) {
        const { data } = await apiClient.patch(`/inbox/conversations/${conversationId}/pin`, { is_pinned: isPinned });
        return data;
    },

    async addNote(conversationId: number, content: string, visibility: 'private' | 'shared' = 'private') {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/notes`, { content, visibility });
        return data;
    },

    async saveDraft(conversationId: number, content: string, metadata: Record<string, unknown> = {}) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/draft`, { content, metadata });
        return data;
    },

    async deleteDraft(conversationId: number) {
        const { data } = await apiClient.delete(`/inbox/conversations/${conversationId}/draft`);
        return data;
    },

    async setTyping(conversationId: number, isTyping: boolean) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/typing`, { is_typing: isTyping });
        return data;
    },

    async markRead(conversationId: number) {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/read`);
        return data;
    },

    async suggestions(conversationId: number): Promise<{ data: InboxSuggestionPackage }> {
        const { data } = await apiClient.post(`/inbox/conversations/${conversationId}/suggestions`);
        return data;
    },

    async sendMessage(conversationId: number, content: string) {
        const { data } = await apiClient.post(`/conversations/${conversationId}/messages`, {
            content,
            content_type: 'text',
            sender_type: 'agent',
        });
        return data;
    },
};