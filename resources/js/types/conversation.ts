export type ConversationStatus = 'new' | 'waiting' | 'assigned' | 'ai_processing' | 'human_reply' | 'resolved' | 'closed' | 'archived';
export type ConversationPriority = 'low' | 'normal' | 'high' | 'urgent';

export type ConversationUserSummary = {
    id: number;
    name: string;
    avatar: string | null;
};

export type ConversationLabel = {
    id: number;
    name: string;
    color: string | null;
};

export type ConversationParticipant = {
    id: number;
    role: string;
    user: ConversationUserSummary | null;
    joined_at: string | null;
    left_at: string | null;
};

export type Message = {
    id: number;
    uuid: string;
    conversation_id: number;
    sender_type: string;
    sender_id: number | null;
    content: string;
    content_type: string;
    status: string | null;
    direction: 'inbound' | 'outbound';
    message_type: string | null;
    external_id: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type ConversationDetail = {
    id: number;
    uuid: string;
    title: string | null;
    status: ConversationStatus;
    status_label?: string;
    status_active?: boolean;
    priority: ConversationPriority;
    priority_label?: string;
    source: string;
    source_label?: string;
    channel: string | null;
    channel_id: string | null;
    handover_mode: string | null;
    handover_reason: string | null;
    handovered_at: string | null;
    customer: { id: number; name: string; phone: string | null; email: string | null; avatar: string | null } | null;
    assigned_to: ConversationUserSummary | null;
    labels: ConversationLabel[];
    participants: ConversationParticipant[];
    last_message?: {
        id: number;
        content: string | null;
        content_type: string | null;
        sender_type: string | null;
        created_at: string | null;
    } | null;
    message_count: number | null;
    last_message_at: string | null;
    closed_at: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};