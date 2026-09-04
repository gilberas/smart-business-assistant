import apiClient from '@/lib/api/client';
import type { ApiResponse, PaginatedResponse } from '@/types/common';
import type {
    RAGSearchResponse,
    RAGContextPackage,
    QueryAnalysis,
    RAGChunk,
    RAGRetrievalLog,
    ChunkingPreview,
    PromptPreview,
    RankingPreview,
    RAGSystemInfo,
} from '@/types/rag';

interface SearchParams {
    query: string;
    customer_id?: number;
    conversation_id?: number;
    sources?: string[];
    tags?: string[];
    categories?: string[];
    max_results?: number;
    min_score?: number;
    strategy?: string;
}

interface ContextParams {
    query: string;
    customer_id?: number;
    conversation_id?: number;
    include_prompt?: boolean;
    user_message?: string;
}

interface ChunkFilters {
    source_type?: string;
    strategy?: string;
    search?: string;
    per_page?: number;
    page?: number;
}

export const ragApi = {
    async search(params: SearchParams): Promise<ApiResponse<RAGSearchResponse>> {
        const { data } = await apiClient.post('/rag/search', params);
        return data;
    },

    async contextPreview(params: ContextParams): Promise<ApiResponse<RAGContextPackage>> {
        const { data } = await apiClient.post('/rag/context', params);
        return data;
    },

    async analyzeQuery(query: string): Promise<ApiResponse<QueryAnalysis>> {
        const { data } = await apiClient.post('/rag/analyze', { query });
        return data;
    },

    async getStrategies(): Promise<ApiResponse<Record<string, unknown>>> {
        const { data } = await apiClient.get('/rag/strategies');
        return data;
    },

    async getSystemInfo(): Promise<ApiResponse<RAGSystemInfo>> {
        const { data } = await apiClient.get('/rag/system-info');
        return data;
    },

    async getChunks(filters?: ChunkFilters): Promise<PaginatedResponse<RAGChunk>> {
        const params = new URLSearchParams();
        if (filters?.source_type) params.set('source_type', filters.source_type);
        if (filters?.strategy) params.set('strategy', filters.strategy);
        if (filters?.search) params.set('search', filters.search);
        if (filters?.per_page) params.set('per_page', String(filters.per_page));
        if (filters?.page) params.set('page', String(filters.page));
        const qs = params.toString();
        const { data } = await apiClient.get(`/rag/chunks${qs ? `?${qs}` : ''}`);
        return data;
    },

    async getChunk(id: number): Promise<ApiResponse<RAGChunk>> {
        const { data } = await apiClient.get(`/rag/chunks/${id}`);
        return data;
    },

    async getChunksBySource(sourceType: string, sourceId?: number): Promise<ApiResponse<RAGChunk[]>> {
        const url = sourceId
            ? `/rag/chunks/by-source/${sourceType}/${sourceId}`
            : `/rag/chunks/by-source/${sourceType}`;
        const { data } = await apiClient.get(url);
        return data;
    },

    async getChunkStats(): Promise<ApiResponse<Record<string, unknown>>> {
        const { data } = await apiClient.get('/rag/chunks/stats');
        return data;
    },

    async previewChunking(content: string, strategy?: string): Promise<ApiResponse<ChunkingPreview>> {
        const { data } = await apiClient.post('/rag/chunks/preview', { content, strategy });
        return data;
    },

    async promptPreview(params: {
        query: string;
        user_message: string;
        customer_id?: number;
        conversation_id?: number;
    }): Promise<ApiResponse<PromptPreview>> {
        const { data } = await apiClient.post('/rag/prompt-preview', params);
        return data;
    },

    async rankingPreview(params: {
        query: string;
        source_type: string;
        source_id: number;
    }): Promise<ApiResponse<RankingPreview>> {
        const { data } = await apiClient.post('/rag/ranking-preview', params);
        return data;
    },

    async getLogs(filters?: Record<string, string | number>): Promise<PaginatedResponse<RAGRetrievalLog>> {
        const params = new URLSearchParams();
        Object.entries(filters || {}).forEach(([k, v]) => {
            if (v) params.set(k, String(v));
        });
        const qs = params.toString();
        const { data } = await apiClient.get(`/rag/logs${qs ? `?${qs}` : ''}`);
        return data;
    },

    async getLog(id: number): Promise<ApiResponse<RAGRetrievalLog>> {
        const { data } = await apiClient.get(`/rag/logs/${id}`);
        return data;
    },
};
