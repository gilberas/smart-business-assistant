export type BroadcastCampaignStatus =
  | 'draft' | 'scheduled' | 'running' | 'paused'
  | 'completed' | 'cancelled' | 'archived' | 'failed';

export type BroadcastRecipientStatus =
  | 'pending' | 'queued' | 'sent' | 'delivered'
  | 'read' | 'failed' | 'expired' | 'rejected';

export type AudienceFilterOperator =
  | 'equals' | 'not_equals' | 'in' | 'not_in' | 'contains'
  | 'greater_than' | 'less_than' | 'date_before' | 'date_after'
  | 'is_set' | 'is_not_set';

export type CampaignTemplate = {
  id: number;
  name: string;
  language: string;
  category: string;
  status: string;
  body: string;
  header?: Record<string, unknown> | null;
  footer?: string | null;
  buttons?: Record<string, unknown>[] | null;
  meta_template_id?: string | null;
  created_at: string;
  updated_at: string;
};

export type BroadcastCampaign = {
  id: number;
  uuid: string;
  name: string;
  status: BroadcastCampaignStatus;
  status_label: string;
  template?: CampaignTemplate | null;
  template_id?: number | null;
  message_content?: string | null;
  recipient_filter?: Record<string, unknown> | null;
  scheduled_at?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  sent_at?: string | null;
  creator?: { id: number; name: string } | null;
  recipients_count: number;
  estimated_recipients?: number | null;
  metadata?: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
};

export type CampaignStats = {
  total: number;
  pending: number;
  queued: number;
  sent: number;
  delivered: number;
  read: number;
  failed: number;
  expired: number;
  rejected: number;
  delivery_rate: number;
  read_rate: number;
};

export type CampaignRecipient = {
  id: number;
  uuid: string;
  status: BroadcastRecipientStatus;
  customer: { id: number; uuid: string; name: string; phone: string } | null;
  error_message?: string | null;
  sent_at?: string | null;
  delivered_at?: string | null;
  read_at?: string | null;
  failed_at?: string | null;
};

export type WhatsAppTemplate = {
  id: number;
  name: string;
  language: string;
  category: string;
  status: string;
  body: string;
  header?: Record<string, unknown> | null;
  footer?: string | null;
  buttons?: Record<string, unknown>[] | null;
  meta_template_id?: string | null;
  created_at: string;
  updated_at: string;
};

export type AudiencePreview = {
  total_count: number;
  preview_customers: Record<string, unknown>[];
  applied_filters: Record<string, unknown>;
};

export type CampaignFilters = {
  status?: BroadcastCampaignStatus | BroadcastCampaignStatus[];
  search?: string;
  created_by?: number;
  page?: number;
  per_page?: number;
  sort_field?: string;
  sort_dir?: 'asc' | 'desc';
};

export type TemplateFilters = {
  status?: string;
  category?: string;
  search?: string;
  page?: number;
  per_page?: number;
};

export type CreateCampaignPayload = {
  name: string;
  template_id?: number | null;
  message_content?: string | null;
  recipient_filter: Record<string, unknown>;
  scheduled_at?: string | null;
  status?: 'draft' | 'scheduled';
  metadata?: Record<string, unknown> | null;
};

export type UpdateCampaignPayload = Partial<CreateCampaignPayload>;

export type CreateTemplatePayload = {
  name: string;
  language: string;
  category: 'MARKETING' | 'UTILITY' | 'AUTHENTICATION';
  body: string;
  header?: { type: string; text?: string } | null;
  footer?: string | null;
  buttons?: Record<string, unknown>[] | null;
};
