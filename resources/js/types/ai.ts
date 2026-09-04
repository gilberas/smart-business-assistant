export type AIProvider = {
    id: number;
    name: string;
    slug: string;
    base_url: string | null;
    is_active: boolean;
    is_default: boolean;
    priority: number;
    metadata: Record<string, unknown> | null;
    models?: AIModel[];
    created_at: string;
};

export type AIModel = {
    id: number;
    provider_id: number;
    model_id: string;
    name: string;
    group: string | null;
    capabilities: string[];
    input_price: number;
    output_price: number;
    context_window: number;
    max_output_tokens: number;
    is_active: boolean;
};

export type AIConfiguration = {
    id?: number;
    business_id?: number;
    provider: string;
    model: string;
    temperature: number;
    top_p: number;
    max_tokens: number;
    timeout_seconds: number;
    streaming: boolean;
    language: string;
    safety_settings: Record<string, unknown> | null;
    system_prompt: Record<string, unknown> | null;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
};

export type PromptTemplate = {
    id: number;
    business_id: number | null;
    name: string;
    slug: string;
    type: PromptType;
    content: string;
    description: string | null;
    variables: string[];
    version: number;
    is_active: boolean;
    versions?: PromptVersion[];
    created_at: string;
    updated_at: string;
};

export type PromptVersion = {
    id: number;
    version: number;
    content: string;
    changelog: string | null;
    variables: string[];
    created_by: { id: number; name: string } | null;
    created_at: string;
};

export type PromptType =
    | 'system'
    | 'business'
    | 'conversation'
    | 'knowledge'
    | 'classification'
    | 'moderation'
    | 'summarization';

export type AIRequest = {
    id: number;
    business_id: number | null;
    user_id: number | null;
    conversation_id: number | null;
    provider: string;
    model: string;
    status: AIRequestStatus;
    finish_reason: string | null;
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
    estimated_cost: number;
    duration_ms: number | null;
    error_message: string | null;
    created_at: string;
};

export type AIRequestStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'streaming';

export type AIUsage = {
    id: number;
    business_id: number;
    provider: string;
    model: string;
    date: string;
    total_requests: number;
    successful_requests: number;
    failed_requests: number;
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
    estimated_cost: number;
    total_duration_ms: number;
};

export type ProviderHealth = {
    healthy: boolean;
    latency_ms: number;
    checked_at: string;
    error?: string;
};

export type AIGeneratePayload = {
    messages: { role: string; content: string }[];
    model?: string;
    temperature?: number;
    top_p?: number;
    max_tokens?: number;
    stream?: boolean;
};

export type AIGenerateResponse = {
    content: string;
    finish_reason: string | null;
    usage: {
        prompt_tokens: number;
        completion_tokens: number;
        total_tokens: number;
        estimated_cost: number;
    } | null;
    latency_ms: number | null;
    status: string;
    error: string | null;
    provider: string | null;
    model: string | null;
};
