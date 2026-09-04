import { useQuery } from '@tanstack/react-query';
import { analyticsApi } from '@/lib/api/analytics';
import type { AnalyticsFilter } from '@/types/analytics';

const ANALYTICS_QUERY_OPTIONS = {
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
} as const;

export function useOverview(filters?: AnalyticsFilter, enabled = true) {
    return useQuery({
        queryKey: ['analytics', 'overview', filters],
        queryFn: () => analyticsApi.overview(filters),
        enabled,
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useConversationAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'conversations', filters],
        queryFn: () => analyticsApi.conversations(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useCustomerAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'customers', filters],
        queryFn: () => analyticsApi.customers(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useAIAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'ai', filters],
        queryFn: () => analyticsApi.ai(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useAgentAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'agents', filters],
        queryFn: () => analyticsApi.agents(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useCampaignAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'campaigns', filters],
        queryFn: () => analyticsApi.campaigns(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useWorkflowAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'workflows', filters],
        queryFn: () => analyticsApi.workflows(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}

export function useKnowledgeAnalytics(filters?: AnalyticsFilter) {
    return useQuery({
        queryKey: ['analytics', 'knowledge', filters],
        queryFn: () => analyticsApi.knowledge(filters),
        ...ANALYTICS_QUERY_OPTIONS,
    });
}
