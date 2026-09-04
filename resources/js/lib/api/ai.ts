import apiClient from '@/lib/api/client';
import type {
    AIConfiguration,
    AIUsage,
    PromptTemplate,
    AIRequest,
    AIProvider,
    AIGeneratePayload,
    AIGenerateResponse,
    ProviderHealth,
} from '@/types/ai';
import type { PaginatedResponse, ApiResponse } from '@/types/common';

function buildQuery(filters?: Record<string, string | number | boolean | undefined>): string {
    if (!filters) {
        return '';
    }

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '') {
            params.append(key, String(value));
        }
    });
    const qs = params.toString();

    return qs ? `?${qs}` : '';
}

export const aiApi = {
    // Configuration
    async getConfiguration(): Promise<ApiResponse<AIConfiguration>> {
        const { data } = await apiClient.get('/ai/configuration');

        return data;
    },

    async updateConfiguration(
        payload: Partial<AIConfiguration>,
    ): Promise<ApiResponse<AIConfiguration>> {
        const { data } = await apiClient.put('/ai/configuration', payload);

        return data;
    },

    async getResolvedConfiguration(): Promise<{ data: AIConfiguration }> {
        const { data } = await apiClient.get('/ai/configuration/resolved');

        return data;
    },

    // Prompt Templates
    async listPrompts(filters?: { type?: string }): Promise<PaginatedResponse<PromptTemplate>> {
        const { data } = await apiClient.get(`/prompts${buildQuery(filters)}`);

        return data;
    },

    async getPrompt(id: number): Promise<ApiResponse<PromptTemplate>> {
        const { data } = await apiClient.get(`/prompts/${id}`);

        return data;
    },

    async createPrompt(payload: {
        name: string;
        type: string;
        content: string;
        description?: string;
    }): Promise<ApiResponse<PromptTemplate>> {
        const { data } = await apiClient.post('/prompts', payload);

        return data;
    },

    async updatePrompt(
        id: number,
        payload: Partial<PromptTemplate> & { changelog?: string },
    ): Promise<ApiResponse<PromptTemplate>> {
        const { data } = await apiClient.put(`/prompts/${id}`, payload);

        return data;
    },

    async deletePrompt(id: number): Promise<void> {
        await apiClient.delete(`/prompts/${id}`);
    },

    async testPrompt(
        id: number,
        variables?: Record<string, string>,
    ): Promise<ApiResponse<{ rendered: string; variables: string[]; token_estimate: number }>> {
        const { data } = await apiClient.post(`/prompts/${id}/test`, { variables });

        return data;
    },

    // Generation
    async generate(payload: AIGeneratePayload): Promise<AIGenerateResponse> {
        const { data } = await apiClient.post('/ai/generate', payload);

        return data;
    },

    // Request History
    async listRequests(
        filters?: Record<string, string | number | boolean | undefined>,
    ): Promise<PaginatedResponse<AIRequest>> {
        const { data } = await apiClient.get(`/ai/requests${buildQuery(filters)}`);

        return data;
    },

    async getRequest(id: number): Promise<ApiResponse<AIRequest>> {
        const { data } = await apiClient.get(`/ai/requests/${id}`);

        return data;
    },

    // Usage
    async getUsage(filters?: { date_from?: string; date_to?: string; provider?: string }): Promise<{
        data: AIUsage[];
        totals: {
            total_requests: number;
            successful_requests: number;
            failed_requests: number;
            prompt_tokens: number;
            completion_tokens: number;
            total_tokens: number;
            estimated_cost: number;
        };
        by_provider: {
            provider: string;
            total_requests: number;
            successful_requests: number;
            failed_requests: number;
            total_tokens: number;
            estimated_cost: number;
        }[];
    }> {
        const { data } = await apiClient.get(`/ai/usage${buildQuery(filters)}`);

        return data;
    },

    async getUsageTotals(filters?: {
        date_from?: string;
        date_to?: string;
    }): Promise<{ data: { total_requests: number; estimated_cost: number } }> {
        const { data } = await apiClient.get(`/ai/usage/totals${buildQuery(filters)}`);

        return data;
    },

    async getUsageByProvider(filters?: {
        date_from?: string;
        date_to?: string;
    }): Promise<{ data: { provider: string; total_requests: number; estimated_cost: number }[] }> {
        const { data } = await apiClient.get(`/ai/usage/by-provider${buildQuery(filters)}`);

        return data;
    },

    // Providers
    async listProviders(): Promise<ApiResponse<AIProvider[]>> {
        const { data } = await apiClient.get('/ai/providers');

        return data;
    },

    async getProvider(id: number): Promise<ApiResponse<AIProvider>> {
        const { data } = await apiClient.get(`/ai/providers/${id}`);

        return data;
    },

    async syncProviderModels(id: number): Promise<{ message: string; count: number }> {
        const { data } = await apiClient.post(`/ai/providers/${id}/sync-models`);

        return data;
    },

    async checkProviderHealth(id: number): Promise<ProviderHealth> {
        const { data } = await apiClient.get(`/ai/providers/${id}/health`);

        return data;
    },

    async getProviderHealthHistory(
        id: number,
        limit?: number,
    ): Promise<{ data: ProviderHealth[] }> {
        const { data } = await apiClient.get(
            `/ai/providers/${id}/health-history${buildQuery({ limit })}`,
        );

        return data;
    },

    async getAvailableProviders(): Promise<{
        data: Record<
            string,
            {
                name: string;
                healthy: boolean;
                models: { id: string; name: string; owned_by?: string }[];
            }
        >;
    }> {
        const { data } = await apiClient.get('/ai/providers/available');

        return data;
    },

    // Health
    async getHealth(): Promise<{ data: Record<string, { healthy: boolean; name: string }> }> {
        const { data } = await apiClient.get('/ai/health');

        return data;
    },

    async listAllModels(): Promise<{ data: Record<string, { id: string; name: string }[]> }> {
        const { data } = await apiClient.get('/ai/models');

        return data;
    },
};
