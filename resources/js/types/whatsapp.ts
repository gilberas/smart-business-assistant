export type WhatsAppAccountStatus = 'connected' | 'disconnected' | 'error' | 'expired';
export type TokenStatus = 'active' | 'expired' | 'revoked';
export type PhoneNumberStatus = 'active' | 'flagged' | 'disabled' | 'reconnect_required';
export type WebhookEventStatus = 'pending' | 'processing' | 'processed' | 'failed' | 'dead_letter';
export type MessageDirection = 'inbound' | 'outbound';
export type MessageType = 'text' | 'image' | 'audio' | 'video' | 'document' | 'sticker' | 'location' | 'contacts' | 'interactive' | 'template' | 'button' | 'list' | 'reaction' | 'system';
export type MessageStatus = 'pending' | 'sent' | 'delivered' | 'read' | 'failed';

export type WhatsAppAccount = {
    id: number;
    uuid: string;
    business_id: number;
    waba_id: string | null;
    token_status: TokenStatus;
    token_expires_at: string | null;
    webhook_verified: boolean;
    webhook_verified_at: string | null;
    last_webhook_received_at: string | null;
    status: WhatsAppAccountStatus;
    connected_at: string | null;
    last_synced_at: string | null;
    phone_numbers?: PhoneNumber[];
    created_at: string;
    updated_at: string;
};

export type PhoneNumber = {
    id: number;
    uuid: string;
    whatsapp_account_id: number | null;
    phone_number: string;
    phone_number_id: string | null;
    display_phone: string | null;
    verified_name: string | null;
    quality_rating: string | null;
    status: PhoneNumberStatus;
    code_verification_status: string | null;
    pin_enabled: boolean;
    messaging_limit_tier: string | null;
    created_at: string;
    updated_at: string;
};

export type WhatsAppMessage = {
    id: number;
    uuid: string;
    business_id: number;
    from_number: string | null;
    to_number: string | null;
    direction: MessageDirection;
    message_type: MessageType;
    meta_message_id: string | null;
    conversation_id: string | null;
    status: MessageStatus;
    body: string | null;
    media_id: string | null;
    caption: string | null;
    template_name: string | null;
    context: Record<string, unknown> | null;
    errors: Record<string, unknown> | null;
    sent_at: string | null;
    delivered_at: string | null;
    read_at: string | null;
    failed_at: string | null;
    media?: WhatsAppMedia[];
    statuses?: MessageStatusEntry[];
    created_at: string;
    updated_at: string;
};

export type WhatsAppMessageList = {
    id: number;
    uuid: string;
    from_number: string | null;
    to_number: string | null;
    direction: MessageDirection;
    message_type: MessageType;
    status: MessageStatus;
    body: string | null;
    created_at: string;
};

export type MessageStatusEntry = {
    id: number;
    message_id: number;
    status: string;
    timestamp: string | null;
    error_code: string | null;
    error_title: string | null;
    error_message: string | null;
    created_at: string;
};

export type WhatsAppMedia = {
    id: number;
    uuid: string;
    media_id: string | null;
    mime_type: string | null;
    file_size: number | null;
    file_name: string | null;
    caption: string | null;
    size_for_humans: string;
    url?: string;
    created_at: string;
};

export type WebhookEvent = {
    id: number;
    uuid: string;
    event_type: string;
    meta_event_id: string | null;
    status: WebhookEventStatus;
    retry_count: number;
    last_error: string | null;
    processed_at: string | null;
    created_at: string;
};

export type AccountDiagnostics = {
    account: {
        id: number;
        uuid: string;
        status: string;
        waba_id: string | null;
        connected_at: string | null;
        last_synced_at: string | null;
        webhook_verified: boolean;
        webhook_verified_at: string | null;
        last_webhook_received_at: string | null;
    };
    token: {
        status: string;
        expires_at: string | null;
        days_left: number | null;
    };
    whatsapp_business_profile: Record<string, unknown> | null;
    phone_numbers: PhoneNumber[];
    message_stats: {
        total: number;
        inbound: number;
        outbound: number;
        sent: number;
        delivered: number;
        read: number;
        failed: number;
    };
};

export type WebhookHealth = {
    connected: boolean;
    webhook_verified: boolean;
    webhook_verified_at: string | null;
    last_received: string | null;
    pending_events: number;
    failed_events: number;
    dead_letter_events: number;
};

export type SendMessagePayload = {
    to: string;
    type: MessageType | 'location';
    body?: string;
    media_id?: string;
    media_url?: string;
    caption?: string;
    template_name?: string;
    template_language?: string;
    template_params?: Record<string, unknown>[];
    reply_to_message_id?: string;
    latitude?: number;
    longitude?: number;
    location_name?: string;
    location_address?: string;
    interactive_buttons?: { id: string; title: string }[];
    list_header?: string;
    list_body?: string;
    list_footer?: string;
    list_button_text?: string;
    list_sections?: Record<string, unknown>[];
    schedule?: boolean;
    schedule_minutes?: number;
};

export type ConnectAccountPayload = {
    access_token: string;
    waba_id?: string;
    phone_number_id?: string;
};

export type MessageStats = {
    total: number;
    inbound: number;
    outbound: number;
    sent: number;
    delivered: number;
    read: number;
    failed: number;
};
