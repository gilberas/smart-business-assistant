export type Business = {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    website: string | null;
    logo_url: string | null;
    timezone: string;
    locale: string;
    currency: string;
    status: string;
    industry: string | null;
    employee_count: number | null;
    onboarding_step: string | null;
    onboarding_completed_at: string | null;
    branding: Branding | null;
    preferences: Record<string, unknown> | null;
    member_role: string | null;
    member_status: string | null;
    settings?: BusinessSettings | null;
    trial_ends_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Branding = {
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    font_family: string;
    logo_url: string | null;
    favicon_url: string | null;
};

export type BusinessSettings = {
    id: number;
    business_id: number;
    business_hours: Record<string, string[] | null> | null;
    ai_provider: string | null;
    ai_model: string | null;
    ai_temperature: number;
    ai_max_tokens: number;
    ai_system_prompt: string | null;
    settings: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type Member = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    role: string;
    status: string;
    joined_at: string | null;
    last_switched_at: string | null;
};

export type CreateBusinessPayload = {
    name: string;
    slug?: string;
    email?: string;
    phone?: string;
    address?: string;
    website?: string;
    timezone?: string;
    locale?: string;
    currency?: string;
    industry?: string;
    employee_count?: number;
    onboarding_step?: string;
};

export type OnboardingStep = 'business-info' | 'details' | 'preferences';

export type UpdateBusinessPayload = Partial<CreateBusinessPayload> & {
    logo_url?: string;
    branding?: Partial<Branding>;
    preferences?: Record<string, unknown>;
};

export type UpdateBusinessSettingsPayload = {
    business_hours?: Record<string, string[] | null>;
    ai_provider?: string;
    ai_model?: string;
    ai_temperature?: number;
    ai_max_tokens?: number;
    ai_system_prompt?: string;
    settings?: Record<string, unknown>;
};
