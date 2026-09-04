import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { inboxApi, type InboxFilters } from '@/lib/api/inbox';
import type { InboxSuggestionPackage } from '@/types/inbox';

export function useInboxConversations(filters: InboxFilters) {
    return useQuery({
        queryKey: ['inbox-conversations', filters],
        queryFn: async () => inboxApi.list(filters),
    });
}

export function useInboxWorkspace(conversationId: number | null) {
    return useQuery({
        queryKey: ['inbox-workspace', conversationId],
        queryFn: async () => inboxApi.show(conversationId!),
        enabled: !!conversationId,
    });
}

export function useInboxActions(conversationId: number | null) {
    const queryClient = useQueryClient();

    const invalidate = async () => {
        await queryClient.invalidateQueries({ queryKey: ['inbox-conversations'] });
        await queryClient.invalidateQueries({ queryKey: ['inbox-workspace', conversationId] });
    };

    return {
        assign: useMutation<unknown, Error, { userId: number; reason?: string }>({ mutationFn: async (params) => inboxApi.assign(conversationId!, params.userId, params.reason), onSuccess: invalidate }),
        takeover: useMutation<unknown, Error, string | undefined>({ mutationFn: async (reason) => inboxApi.takeover(conversationId!, reason), onSuccess: invalidate }),
        release: useMutation<unknown, Error, string | undefined>({ mutationFn: async (reason) => inboxApi.release(conversationId!, reason), onSuccess: invalidate }),
        updateStatus: useMutation<unknown, Error, string>({ mutationFn: async (status) => inboxApi.updateStatus(conversationId!, status), onSuccess: invalidate }),
        togglePin: useMutation<unknown, Error, boolean>({ mutationFn: async (isPinned) => inboxApi.togglePin(conversationId!, isPinned), onSuccess: invalidate }),
        addNote: useMutation<unknown, Error, string>({ mutationFn: async (content) => inboxApi.addNote(conversationId!, content), onSuccess: invalidate }),
        saveDraft: useMutation<unknown, Error, string>({ mutationFn: async (content) => inboxApi.saveDraft(conversationId!, content), onSuccess: invalidate }),
        deleteDraft: useMutation<unknown, Error, void>({ mutationFn: async () => inboxApi.deleteDraft(conversationId!), onSuccess: invalidate }),
        typing: useMutation<unknown, Error, boolean>({ mutationFn: async (isTyping) => inboxApi.setTyping(conversationId!, isTyping) }),
        read: useMutation<unknown, Error, void>({ mutationFn: async () => inboxApi.markRead(conversationId!) }),
        suggestions: useMutation<{ data: InboxSuggestionPackage }, Error, void>({ mutationFn: async () => inboxApi.suggestions(conversationId!) }),
        sendMessage: useMutation<unknown, Error, string>({ mutationFn: async (content) => inboxApi.sendMessage(conversationId!, content), onSuccess: invalidate }),
    };
}