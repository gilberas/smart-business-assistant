import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { customerApi } from '@/lib/api/customer';
import { useCustomerStore } from '@/stores/customer-store';
import type {
    Customer,
    CustomerFilters,
    CreateCustomerPayload,
    UpdateCustomerPayload,
    MergeCustomersPayload,
} from '@/types/customer';

export function useCustomers(filters?: CustomerFilters & { page?: number; per_page?: number; sort?: string; q?: string }) {
    const setCustomers = useCustomerStore((s) => s.setCustomers);
    const setLoading = useCustomerStore((s) => s.setLoading);

    return useQuery({
        queryKey: ['customers', filters],
        queryFn: async () => {
            setLoading(true);
            try {
                const data = await customerApi.list(filters);
                setCustomers(data.data as unknown as Customer[]);
                return data;
            } finally {
                setLoading(false);
            }
        },
    });
}

export function useCustomer(uuid: string | undefined) {
    const setSelectedCustomer = useCustomerStore((s) => s.setSelectedCustomer);

    return useQuery({
        queryKey: ['customer', uuid],
        queryFn: async () => {
            const customer = await customerApi.get(uuid!);
            setSelectedCustomer(customer);
            return customer;
        },
        enabled: !!uuid,
    });
}

export function useCreateCustomer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateCustomerPayload) => customerApi.create(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}

export function useUpdateCustomer(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateCustomerPayload) => customerApi.update(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer', uuid] });
        },
    });
}

export function useDeleteCustomer() {
    const queryClient = useQueryClient();
    const removeCustomer = useCustomerStore((s) => s.removeCustomer);

    return useMutation({
        mutationFn: (uuid: string) => customerApi.delete(uuid),
        onSuccess: (_data, uuid) => {
            removeCustomer(uuid);
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}

export function useArchiveCustomer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (uuid: string) => customerApi.archive(uuid),
        onSuccess: (_data, uuid) => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}

export function useRestoreCustomer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (uuid: string) => customerApi.restore(uuid),
        onSuccess: (_data, uuid) => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}

export function useCustomerStats() {
    const setStats = useCustomerStore((s) => s.setStats);

    return useQuery({
        queryKey: ['customer-stats'],
        queryFn: async () => {
            const stats = await customerApi.stats();
            setStats(stats);
            return stats;
        },
        staleTime: 2 * 60 * 1000,
    });
}

export function useCustomerNotes(uuid: string | undefined) {
    return useQuery({
        queryKey: ['customer-notes', uuid],
        queryFn: () => customerApi.notes(uuid!),
        enabled: !!uuid,
    });
}

export function useCreateCustomerNote(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (content: string) => customerApi.createNote(uuid!, content),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer-notes', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-activity', uuid] });
        },
    });
}

export function useUpdateCustomerNote(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ noteId, content }: { noteId: number; content: string }) =>
            customerApi.updateNote(uuid!, noteId, content),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer-notes', uuid] });
        },
    });
}

export function useDeleteCustomerNote(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (noteId: number) => customerApi.deleteNote(uuid!, noteId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer-notes', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-activity', uuid] });
        },
    });
}

export function useTogglePinNote(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (noteId: number) => customerApi.togglePinNote(uuid!, noteId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer-notes', uuid] });
        },
    });
}

export function useCustomerActivity(uuid: string | undefined, limit?: number) {
    return useQuery({
        queryKey: ['customer-activity', uuid, limit],
        queryFn: () => customerApi.activity(uuid!, limit),
        enabled: !!uuid,
    });
}

export function useTags() {
    return useQuery({
        queryKey: ['tags'],
        queryFn: () => customerApi.tags(),
        staleTime: 10 * 60 * 1000,
    });
}

export function useCreateTag() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ name, color }: { name: string; color?: string }) =>
            customerApi.createTag(name, color),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['tags'] });
        },
    });
}

export function useUpdateTag() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ tagId, ...payload }: { tagId: number; name?: string; color?: string }) =>
            customerApi.updateTag(tagId, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['tags'] });
        },
    });
}

export function useDeleteTag() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (tagId: number) => customerApi.deleteTag(tagId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['tags'] });
        },
    });
}

export function useAttachTags(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (tags: string[]) => customerApi.attachTags(uuid!, tags),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-activity', uuid] });
        },
    });
}

export function useDetachTags(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (tags: string[]) => customerApi.detachTags(uuid!, tags),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customer', uuid] });
            queryClient.invalidateQueries({ queryKey: ['customer-activity', uuid] });
        },
    });
}

export function useMergeCustomers() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: MergeCustomersPayload) => customerApi.merge(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}

export function useImportPreview() {
    return useMutation({
        mutationFn: (rows: Record<string, unknown>[]) => customerApi.importPreview(rows),
    });
}

export function useImportCustomers() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (rows: Record<string, unknown>[]) => customerApi.import(rows),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers'] });
            queryClient.invalidateQueries({ queryKey: ['customer-stats'] });
        },
    });
}
