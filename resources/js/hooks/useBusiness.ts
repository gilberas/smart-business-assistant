import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { businessApi } from '@/lib/api/business';
import { useBusinessStore } from '@/stores/business-store';
import type {
    CreateBusinessPayload,
    UpdateBusinessPayload,
    UpdateBusinessSettingsPayload,
} from '@/types/business';

export function useBusinesses() {
    const setBusinesses = useBusinessStore((state) => state.setBusinesses);
    const setLoading = useBusinessStore((state) => state.setLoading);

    return useQuery({
        queryKey: ['businesses'],
        queryFn: async () => {
            setLoading(true);

            try {
                const data = await businessApi.list();
                setBusinesses(data.businesses, data.current_business_id);

                return data;
            } finally {
                setLoading(false);
            }
        },
        staleTime: 5 * 60 * 1000,
    });
}

export function useCurrentBusiness() {
    const setCurrentBusiness = useBusinessStore((state) => state.setCurrentBusiness);

    return useQuery({
        queryKey: ['current-business'],
        queryFn: async () => {
            const business = await businessApi.getCurrent();
            setCurrentBusiness(business);

            return business;
        },
        retry: false,
        staleTime: 5 * 60 * 1000,
    });
}

export function useCreateBusiness() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateBusinessPayload) => businessApi.create(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
        },
    });
}

export function useUpdateBusiness() {
    const queryClient = useQueryClient();
    const setCurrentBusiness = useBusinessStore((state) => state.setCurrentBusiness);

    return useMutation({
        mutationFn: ({ id, payload }: { id: number; payload: UpdateBusinessPayload }) =>
            businessApi.update(id, payload),
        onSuccess: (business) => {
            setCurrentBusiness(business);
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
            queryClient.invalidateQueries({ queryKey: ['current-business'] });
        },
    });
}

export function useDeleteBusiness() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => businessApi.delete(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
        },
    });
}

export function useSwitchBusiness() {
    const queryClient = useQueryClient();
    const setCurrentBusiness = useBusinessStore((state) => state.setCurrentBusiness);

    return useMutation({
        mutationFn: (businessId: number) => businessApi.switch(businessId),
        onSuccess: (data) => {
            setCurrentBusiness(data.business);
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
            queryClient.invalidateQueries({ queryKey: ['current-business'] });
            queryClient.invalidateQueries({ queryKey: ['auth-user'] });
            queryClient.invalidateQueries({ queryKey: ['authorization', 'me'] });
            queryClient.invalidateQueries();
        },
    });
}

export function useBusinessMembers(businessId: number | null) {
    return useQuery({
        queryKey: ['business-members', businessId],
        queryFn: () => businessApi.getMembers(businessId!),
        enabled: !!businessId,
    });
}

export function useAddMember(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ email, role }: { email: string; role: string }) =>
            businessApi.addMember(businessId!, email, role),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-members', businessId] });
        },
    });
}

export function useUpdateMemberRole(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ memberId, role }: { memberId: number; role: string }) =>
            businessApi.updateMemberRole(businessId!, memberId, role),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-members', businessId] });
        },
    });
}

export function useRemoveMember(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (memberId: number) => businessApi.removeMember(businessId!, memberId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-members', businessId] });
        },
    });
}

export function useBusinessSettings(businessId: number | null) {
    return useQuery({
        queryKey: ['business-settings', businessId],
        queryFn: () => businessApi.getSettings(businessId!),
        enabled: !!businessId,
    });
}

export function useUpdateBusinessSettings(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateBusinessSettingsPayload) =>
            businessApi.updateSettings(businessId!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-settings', businessId] });
        },
    });
}

export function useUpdateOnboardingStep(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (step: string) => businessApi.updateOnboarding(businessId!, step),
        onSuccess: (business) => {
            queryClient.setQueryData(['current-business'], business);
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
            queryClient.invalidateQueries({ queryKey: ['current-business'] });
        },
    });
}

export function useCompleteOnboarding(businessId: number | null) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => businessApi.completeOnboarding(businessId!),
        onSuccess: (business) => {
            queryClient.setQueryData(['current-business'], business);
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
            queryClient.invalidateQueries({ queryKey: ['current-business'] });
        },
    });
}
