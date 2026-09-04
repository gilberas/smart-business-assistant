import type { ConversationDetail } from '@/types/conversation';
import type { Message } from '@/types/conversation';

export type InboxConversation = ConversationDetail & {
    is_pinned?: boolean;
    is_unread?: boolean;
    last_message_preview?: string | null;
};

export type InboxConversationListResponse = {
    data: InboxConversation[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats?: {
        by_status: Record<string, number>;
        unassigned: number;
    };
};

export type InboxDraft = {
    id: number;
    conversation_id: number;
    user_id: number;
    content: string;
    metadata: Record<string, unknown> | null;
    sent_at: string | null;
    updated_at: string;
    author?: { id: number; name: string } | null;
};

export type InboxWorkspace = {
    conversation: InboxConversation;
    messages: Message[];
    events: Array<{
        id: number;
        type: string;
        payload: Record<string, unknown> | null;
        created_by: { id: number; name: string; avatar: string | null } | null;
        created_at: string;
    }>;
    drafts: InboxDraft[];
    typing: { users: Array<{ user_id: number; name: string; at: string }>; is_typing: boolean };
    read_state: { read_at: string | null; is_unread: boolean };
};

export type InboxSuggestionPackage = {
    decision: {
        decision: 'auto_send' | 'human_review' | 'human_takeover' | 'never_reply';
        reason: string;
        confidence: number;
    };
    context: Record<string, unknown>;
    suggested_reply: string;
    alternative_replies: Array<{ label: string; content: string }>;
    prompt: Record<string, unknown>;
    ai_response: Record<string, unknown>;
};