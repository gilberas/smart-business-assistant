export type CustomerStatus = 'active' | 'archived' | 'inactive' | 'blacklisted';
export type CustomerType = 'individual' | 'business';
export type LifecycleStage = 'lead' | 'prospect' | 'active' | 'at_risk' | 'churned' | 'vip';
export type CustomerSource = 'manual' | 'import' | 'whatsapp' | 'web_form' | 'api' | 'referral' | 'social' | 'other';

export type CustomerTag = {
    id: number;
    business_id: number;
    name: string;
    slug: string;
    color: string | null;
    created_at: string;
    updated_at: string;
    pivot?: { customer_id: number; tag_id: number };
};

export type CustomerNote = {
    id: number;
    customer_id: number;
    author_id: number;
    content: string;
    is_pinned: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    author?: { id: number; name: string; avatar: string | null };
};

export type CustomerAddress = {
    id: number;
    customer_id: number;
    label: string;
    street: string;
    city: string;
    state: string | null;
    postal_code: string | null;
    country: string;
    latitude: number | null;
    longitude: number | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
};

export type CustomerCustomFieldValue = {
    id: number;
    customer_id: number;
    custom_field_id: number;
    value: string;
    custom_field?: { id: number; name: string; key: string; type: string };
};

export type CustomerActivity = {
    id: number;
    customer_id: number;
    actor_id: number | null;
    type: string;
    label: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    actor?: { id: number; name: string; avatar: string | null } | null;
};

export type Customer = {
    id: number;
    uuid: string;
    business_id: number;
    name: string;
    phone: string | null;
    email: string | null;
    avatar: string | null;
    status: CustomerStatus;
    source: CustomerSource;
    customer_type: CustomerType;
    lifecycle_stage: LifecycleStage;
    company_name: string | null;
    job_title: string | null;
    timezone: string;
    preferred_language: string;
    notes: string | null;
    social_links: Record<string, string> | null;
    birthday: string | null;
    loyalty_points: number;
    loyalty_tier: string | null;
    tags: CustomerTag[];
    metadata: Record<string, unknown> | null;
    last_interacted_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    addresses?: CustomerAddress[];
    primary_address?: CustomerAddress | null;
    notes_count?: number;
    activity_count?: number;
};

export type CustomerList = Omit<Customer, 'addresses' | 'notes_count' | 'activity_count' | 'social_links' | 'metadata' | 'notes'> & {
    primary_address?: CustomerAddress | null;
    tags_count?: number;
};

export type CreateCustomerPayload = {
    name: string;
    phone?: string;
    email?: string;
    status?: CustomerStatus;
    source?: CustomerSource;
    customer_type?: CustomerType;
    lifecycle_stage?: LifecycleStage;
    company_name?: string;
    job_title?: string;
    timezone?: string;
    preferred_language?: string;
    notes?: string;
    social_links?: Record<string, string>;
    birthday?: string;
    loyalty_points?: number;
    loyalty_tier?: string;
    metadata?: Record<string, unknown>;
    tags?: string[];
};

export type UpdateCustomerPayload = Partial<CreateCustomerPayload>;

export type CustomerStats = {
    total: number;
    active: number;
    archived: number;
    inactive: number;
    blacklisted: number;
    by_stage: Record<string, number>;
    by_source: Record<string, number>;
    new_today: number;
    new_this_week: number;
    new_this_month: number;
};

export type CustomerFilters = {
    status?: CustomerStatus | CustomerStatus[];
    source?: CustomerSource | CustomerSource[];
    lifecycle_stage?: LifecycleStage | LifecycleStage[];
    customer_type?: CustomerType;
    tags?: string[];
    search?: string;
    created_from?: string;
    created_to?: string;
    last_interacted_from?: string;
    last_interacted_to?: string;
};

export type MergeCustomersPayload = {
    primary_id: number;
    duplicate_id: number;
};

export type ImportRow = {
    name: string;
    phone?: string;
    email?: string;
    [key: string]: unknown;
};

export type ImportPreviewResult = {
    total: number;
    valid: number;
    invalid: number;
    errors: Record<number, string[]>;
    preview: ImportRow[];
};
