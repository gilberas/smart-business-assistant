import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { whatsappApi } from '@/lib/api/whatsapp';
import { useWhatsAppStore } from '@/stores/whatsapp-store';
import type { SendMessagePayload, ConnectAccountPayload } from '@/types/whatsapp';

export function useWhatsAppAccount() {
    const setAccount = useWhatsAppStore((s) => s.setAccount);

    return useQuery({
        queryKey: ['whatsapp', 'account'],
        queryFn: async () => {
            const account = await whatsappApi.getAccount();
            setAccount(account);
            return account;
        },
    });
}

export function useConnectWhatsApp() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ConnectAccountPayload) => whatsappApi.connect(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'account'] });
        },
    });
}

export function useDisconnectWhatsApp() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => whatsappApi.disconnect(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'account'] });
        },
    });
}

export function useReconnectWhatsApp() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (accessToken: string) => whatsappApi.reconnect({ access_token: accessToken }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'account'] });
        },
    });
}

export function useSyncPhoneNumbers() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => whatsappApi.sync(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'account'] });
        },
    });
}

export function useWhatsAppDiagnostics() {
    const setDiagnostics = useWhatsAppStore((s) => s.setDiagnostics);

    return useQuery({
        queryKey: ['whatsapp', 'diagnostics'],
        queryFn: async () => {
            const diagnostics = await whatsappApi.getDiagnostics();
            setDiagnostics(diagnostics);
            return diagnostics;
        },
        enabled: false,
    });
}

export function useWhatsAppMessages(filters?: Record<string, unknown>) {
    return useQuery({
        queryKey: ['whatsapp', 'messages', filters],
        queryFn: () => whatsappApi.listMessages(filters),
    });
}

export function useWhatsAppMessage(id: number) {
    return useQuery({
        queryKey: ['whatsapp', 'message', id],
        queryFn: () => whatsappApi.getMessage(id),
        enabled: !!id,
    });
}

export function useSendWhatsAppMessage() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: SendMessagePayload) => whatsappApi.sendMessage(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'messages'] });
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'stats'] });
        },
    });
}

export function useWhatsAppConversation(phoneNumber: string, limit?: number) {
    return useQuery({
        queryKey: ['whatsapp', 'conversation', phoneNumber, limit],
        queryFn: () => whatsappApi.getConversation(phoneNumber, limit),
        enabled: !!phoneNumber,
    });
}

export function useWhatsAppMessageStats() {
    const setMessageStats = useWhatsAppStore((s) => s.setMessageStats);

    return useQuery({
        queryKey: ['whatsapp', 'stats'],
        queryFn: async () => {
            const stats = await whatsappApi.getMessageStats();
            setMessageStats(stats);
            return stats;
        },
    });
}

export function useWhatsAppMedia(filters?: Record<string, unknown>) {
    return useQuery({
        queryKey: ['whatsapp', 'media', filters],
        queryFn: () => whatsappApi.listMedia(filters),
    });
}

export function useUploadWhatsAppMedia() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ file, type }: { file: File; type?: string }) => whatsappApi.uploadMedia(file, type),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'media'] });
        },
    });
}

export function useDeleteWhatsAppMedia() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => whatsappApi.deleteMedia(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['whatsapp', 'media'] });
        },
    });
}

export function useWebhookHealth() {
    const setWebhookHealth = useWhatsAppStore((s) => s.setWebhookHealth);

    return useQuery({
        queryKey: ['whatsapp', 'webhook', 'health'],
        queryFn: async () => {
            const health = await whatsappApi.getWebhookHealth();
            setWebhookHealth(health);
            return health;
        },
    });
}

export function useWebhookEvents(filters?: Record<string, unknown>) {
    return useQuery({
        queryKey: ['whatsapp', 'webhook', 'events', filters],
        queryFn: () => whatsappApi.listWebhookEvents(filters),
    });
}

export function useWhatsAppStatus() {
    return useQuery({
        queryKey: ['whatsapp', 'status'],
        queryFn: () => whatsappApi.getStatus(),
    });
}
