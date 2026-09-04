import { useQuery, useMutation } from '@tanstack/react-query';
import { ragApi } from '@/lib/api/rag';
import type { RAGSearchResponse, RAGContextPackage } from '@/types/rag';

export function useRAGSearch(params: Record<string, unknown> | null) {
    return useQuery({
        queryKey: ['rag-search', params],
        queryFn: async () => {
            const data = await ragApi.search(params as Record<string, string>);
            return data.data as RAGSearchResponse;
        },
        enabled: !!params?.query,
    });
}

export function useRAGContext(params: Record<string, unknown> | null) {
    return useQuery({
        queryKey: ['rag-context', params],
        queryFn: async () => {
            const data = await ragApi.contextPreview(params as Record<string, string>);
            return data.data as RAGContextPackage;
        },
        enabled: !!params?.query,
    });
}

export function useQueryAnalysis(query: string | null) {
    return useQuery({
        queryKey: ['rag-analyze', query],
        queryFn: async () => {
            const data = await ragApi.analyzeQuery(query!);
            return data.data;
        },
        enabled: !!query,
    });
}

export function useRAGStrategies() {
    return useQuery({
        queryKey: ['rag-strategies'],
        queryFn: async () => {
            const data = await ragApi.getStrategies();
            return data.data;
        },
    });
}

export function useRAGSystemInfo() {
    return useQuery({
        queryKey: ['rag-system-info'],
        queryFn: async () => {
            const data = await ragApi.getSystemInfo();
            return data.data;
        },
    });
}

export function useRAGChunks(filters?: Record<string, unknown>) {
    return useQuery({
        queryKey: ['rag-chunks', filters],
        queryFn: async () => {
            const data = await ragApi.getChunks(filters as Record<string, string>);
            return data;
        },
    });
}

export function useRAGChunk(id: number | null) {
    return useQuery({
        queryKey: ['rag-chunk', id],
        queryFn: async () => {
            const data = await ragApi.getChunk(id!);
            return data.data;
        },
        enabled: !!id,
    });
}

export function useRAGChunkStats() {
    return useQuery({
        queryKey: ['rag-chunk-stats'],
        queryFn: async () => {
            const data = await ragApi.getChunkStats();
            return data.data;
        },
    });
}

export function useRAGLogs(filters?: Record<string, unknown>) {
    return useQuery({
        queryKey: ['rag-logs', filters],
        queryFn: async () => {
            const data = await ragApi.getLogs(filters as Record<string, string>);
            return data;
        },
    });
}

export function useRAGChunkingPreview() {
    return useMutation({
        mutationFn: async (params: { content: string; strategy?: string }) => {
            const data = await ragApi.previewChunking(params.content, params.strategy);
            return data.data;
        },
    });
}

export function useRAGPromptPreview() {
    return useMutation({
        mutationFn: async (params: {
            query: string;
            user_message: string;
            customer_id?: number;
            conversation_id?: number;
        }) => {
            const data = await ragApi.promptPreview(params);
            return data.data;
        },
    });
}

export function useRAGRankingPreview() {
    return useMutation({
        mutationFn: async (params: {
            query: string;
            source_type: string;
            source_id: number;
        }) => {
            const data = await ragApi.rankingPreview(params);
            return data.data;
        },
    });
}
