import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { aiApi } from '@/lib/api/ai';
import { useAIStore } from '@/stores/ai-store';
import type { AIConfiguration } from '@/types/ai';

export function useAIConfiguration() {
    const setConfiguration = useAIStore((s) => s.setConfiguration);
    return useQuery({
        queryKey: ['ai-configuration'],
        queryFn: async () => {
            const data = await aiApi.getConfiguration();
            if (data.data) setConfiguration(data.data as AIConfiguration);
            return data;
        },
    });
}

export function useUpdateAIConfiguration() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: Partial<AIConfiguration>) => aiApi.updateConfiguration(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['ai-configuration'] });
        },
    });
}

export function usePrompts(filters?: { type?: string }) {
    return useQuery({
        queryKey: ['prompts', filters],
        queryFn: () => aiApi.listPrompts(filters),
    });
}

export function usePrompt(id: number) {
    return useQuery({
        queryKey: ['prompts', id],
        queryFn: () => aiApi.getPrompt(id),
        enabled: id > 0,
    });
}

export function useCreatePrompt() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: { name: string; type: string; content: string; description?: string }) => aiApi.createPrompt(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['prompts'] });
        },
    });
}

export function useUpdatePrompt() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, ...payload }: { id: number } & Partial<{ name: string; type: string; content: string; description?: string; is_active: boolean; changelog: string }>) =>
            aiApi.updatePrompt(id, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['prompts'] });
        },
    });
}

export function useDeletePrompt() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => aiApi.deletePrompt(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['prompts'] });
        },
    });
}

export function useTestPrompt() {
    return useMutation({
        mutationFn: ({ id, variables }: { id: number; variables?: Record<string, string> }) => aiApi.testPrompt(id, variables),
    });
}

export function useAIUsage(filters?: { date_from?: string; date_to?: string; provider?: string }) {
    return useQuery({
        queryKey: ['ai-usage', filters],
        queryFn: () => aiApi.getUsage(filters),
    });
}

export function useAIRequests(filters?: Record<string, string | number | boolean | undefined>) {
    return useQuery({
        queryKey: ['ai-requests', filters],
        queryFn: () => aiApi.listRequests(filters),
    });
}

export function useAvailableProviders() {
    return useQuery({
        queryKey: ['ai-providers-available'],
        queryFn: () => aiApi.getAvailableProviders(),
    });
}

export function useAIHealth() {
    return useQuery({
        queryKey: ['ai-health'],
        queryFn: () => aiApi.getHealth(),
    });
}

export function useGenerateText() {
    return useMutation({
        mutationFn: (payload: { messages: { role: string; content: string }[]; model?: string; temperature?: number }) =>
            aiApi.generate(payload),
    });
}

export function useSyncProviderModels() {
    return useMutation({
        mutationFn: (id: number) => aiApi.syncProviderModels(id),
    });
}
