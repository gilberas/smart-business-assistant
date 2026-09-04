import apiClient from '@/lib/api/client';
import type {
    Customer,
    CustomerList,
    CustomerActivity,
    CustomerNote,
    CustomerTag,
    CustomerStats,
    CreateCustomerPayload,
    UpdateCustomerPayload,
    CustomerFilters,
    MergeCustomersPayload,
    ImportPreviewResult,
} from '@/types/customer';
import type { PaginatedResponse, ApiResponse } from '@/types/common';

function buildQuery(filters?: CustomerFilters & { page?: number; per_page?: number; sort?: string; q?: string }): string {
    if (!filters) return '';
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`${key}[]`, v));
        } else {
            params.append(key, String(value));
        }
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
}

export const customerApi = {
    async list(filters?: CustomerFilters & { page?: number; per_page?: number; sort?: string; q?: string }): Promise<PaginatedResponse<CustomerList>> {
        const { data } = await apiClient.get(`/customers${buildQuery(filters)}`);
        return data;
    },

    async create(payload: CreateCustomerPayload): Promise<Customer> {
        const { data } = await apiClient.post('/customers', payload);
        return data.data;
    },

    async get(uuid: string): Promise<Customer> {
        const { data } = await apiClient.get(`/customers/${uuid}`);
        return data.data;
    },

    async update(uuid: string, payload: UpdateCustomerPayload): Promise<Customer> {
        const { data } = await apiClient.put(`/customers/${uuid}`, payload);
        return data.data;
    },

    async delete(uuid: string): Promise<void> {
        await apiClient.delete(`/customers/${uuid}`);
    },

    async archive(uuid: string): Promise<Customer> {
        const { data } = await apiClient.post(`/customers/${uuid}/archive`);
        return data.data;
    },

    async restore(uuid: string): Promise<Customer> {
        const { data } = await apiClient.post(`/customers/${uuid}/restore`);
        return data.data;
    },

    async search(q: string): Promise<CustomerList[]> {
        const { data } = await apiClient.get(`/customers/search?q=${encodeURIComponent(q)}`);
        return data.data;
    },

    async stats(): Promise<CustomerStats> {
        const { data } = await apiClient.get('/customers/stats');
        return data;
    },

    async notes(customerUuid: string): Promise<CustomerNote[]> {
        const { data } = await apiClient.get(`/customers/${customerUuid}/notes`);
        return data.data;
    },

    async createNote(customerUuid: string, content: string): Promise<CustomerNote> {
        const { data } = await apiClient.post(`/customers/${customerUuid}/notes`, { content });
        return data.data;
    },

    async updateNote(customerUuid: string, noteId: number, content: string): Promise<CustomerNote> {
        const { data } = await apiClient.put(`/customers/${customerUuid}/notes/${noteId}`, { content });
        return data.data;
    },

    async deleteNote(customerUuid: string, noteId: number): Promise<void> {
        await apiClient.delete(`/customers/${customerUuid}/notes/${noteId}`);
    },

    async togglePinNote(customerUuid: string, noteId: number): Promise<CustomerNote> {
        const { data } = await apiClient.post(`/customers/${customerUuid}/notes/${noteId}/pin`);
        return data.data;
    },

    async activity(customerUuid: string, limit?: number): Promise<CustomerActivity[]> {
        const qs = limit ? `?limit=${limit}` : '';
        const { data } = await apiClient.get(`/customers/${customerUuid}/activity${qs}`);
        return data.data;
    },

    async tags(): Promise<CustomerTag[]> {
        const { data } = await apiClient.get('/tags');
        return data.data;
    },

    async createTag(name: string, color?: string): Promise<CustomerTag> {
        const { data } = await apiClient.post('/tags', { name, color });
        return data.data;
    },

    async updateTag(tagId: number, payload: { name?: string; color?: string }): Promise<CustomerTag> {
        const { data } = await apiClient.put(`/tags/${tagId}`, payload);
        return data.data;
    },

    async deleteTag(tagId: number): Promise<void> {
        await apiClient.delete(`/tags/${tagId}`);
    },

    async attachTags(customerUuid: string, tags: string[]): Promise<void> {
        await apiClient.post(`/customers/${customerUuid}/tags/attach`, { tags });
    },

    async detachTags(customerUuid: string, tags: string[]): Promise<void> {
        await apiClient.post(`/customers/${customerUuid}/tags/detach`, { tags });
    },

    async merge(payload: MergeCustomersPayload): Promise<Customer> {
        const { data } = await apiClient.post('/customers/merge', payload);
        return data.data;
    },

    async importPreview(rows: Record<string, unknown>[]): Promise<ImportPreviewResult> {
        const { data } = await apiClient.post('/customers/import/preview', { rows });
        return data;
    },

    async import(rows: Record<string, unknown>[]): Promise<{ imported: number; skipped: number }> {
        const { data } = await apiClient.post('/customers/import', { rows });
        return data;
    },

    async exportCsv(filters?: CustomerFilters): Promise<Blob> {
        const response = await apiClient.get(`/customers/export${buildQuery(filters)}`, {
            responseType: 'blob',
        });
        return response.data;
    },

    async exportHeaders(): Promise<string[]> {
        const { data } = await apiClient.get('/customers/export/headers');
        return data.headers;
    },
};
