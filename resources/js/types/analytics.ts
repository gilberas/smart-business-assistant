export type DatePreset =
  | 'today' | 'yesterday' | 'last_7_days' | 'last_30_days'
  | 'last_90_days' | 'this_month' | 'last_month' | 'all_time' | 'custom';

export type AnalyticsFilter = {
  preset?: DatePreset;
  start?: string;
  end?: string;
  group_by?: 'hour' | 'day' | 'week' | 'month';
  status?: string;
  channel?: string;
  mode?: 'ai' | 'human';
  agent_id?: number;
  customer_id?: number;
  campaign_id?: number;
  workflow_id?: number;
  tag_ids?: number[];
};

export type KpiData = {
  key: string;
  label: string;
  value: number;
  previous_value?: number | null;
  change_percent?: number | null;
  format: 'number' | 'percent' | 'currency' | 'duration';
  icon?: string | null;
  tooltip?: string | null;
};

export type ChartDataset = {
  label: string;
  data: number[];
  color?: string;
};

export type ChartData = {
  labels: string[];
  datasets: ChartDataset[];
  title?: string;
  type: 'line' | 'bar' | 'doughnut' | 'pie';
};

export type OverviewData = {
  kpis: KpiData[];
  conversation_trend: ChartData;
  customer_trend: ChartData;
  ai_performance: ChartData;
  channel_distribution: ChartData;
  message_volume: ChartData;
  recent_activity: Record<string, unknown>[];
};

export type ConversationAnalytics = {
  total: number;
  total_change_percent: number | null;
  open: number;
  closed: number;
  reopened: number;
  ai_handled: number;
  human_handled: number;
  handover_count: number;
  handover_rate: number;
  resolution_rate: number;
  avg_duration_minutes: number | null;
  peak_hours: Record<string, number>;
  peak_days: Record<string, number>;
  trend: ChartData;
  breakdown: ChartData;
};

export type CustomerAnalytics = {
  total: number;
  new: number;
  new_change_percent: number | null;
  interacted: number;
  inactive: number;
  returning: number;
  by_lifecycle_stage: Record<string, number>;
  by_loyalty_tier: Record<string, number>;
  by_country: Record<string, number>;
  trend: ChartData;
};

export type AIAnalytics = {
  total_requests: number;
  successful_requests: number;
  failed_requests: number;
  success_rate: number;
  prompt_tokens: number;
  completion_tokens: number;
  total_tokens: number;
  estimated_cost: number;
  avg_latency_ms: number;
  ai_resolution_rate: number;
  handover_rate: number;
  handover_count: number;
  low_confidence_responses: number;
  by_provider: Record<string, number>;
  by_model: Record<string, number>;
  usage_by_date: Record<string, unknown>[];
  trend: ChartData;
};

export type AgentStat = {
  agent_id: number;
  agent_name: string;
  conversations_assigned: number;
  conversations_resolved: number;
  resolution_rate: number;
  messages_sent: number;
  avg_first_response_seconds: number | null;
  handover_count: number;
};

export type AgentAnalytics = {
  total_assigned: number;
  total_resolved: number;
  total_agent_messages: number;
  avg_first_response_seconds: number | null;
  avg_resolution_minutes: number | null;
  agent_stats: AgentStat[];
};

export type CampaignPerformance = {
  id: number;
  name: string;
  status: string;
  total_recipients: number;
  sent_recipients: number;
  delivered_recipients: number;
  read_recipients: number;
  failed_recipients: number;
};

export type CampaignAnalytics = {
  total_campaigns: number;
  sent: number;
  draft: number;
  scheduled: number;
  completed: number;
  failed_campaigns: number;
  total_recipients: number;
  sent_recipients: number;
  delivered: number;
  read: number;
  failed_recipients: number;
  delivery_rate: number;
  read_rate: number;
  failure_rate: number;
  campaign_performance: CampaignPerformance[];
};

export type WorkflowAnalytics = {
  total_runs: number;
  successful: number;
  failed: number;
  pending: number;
  running: number;
  success_rate: number;
  failure_rate: number;
  avg_execution_seconds: number | null;
  most_used_workflows: Record<string, unknown>[];
  most_successful_workflows: Record<string, number>;
  error_stages: Record<string, number>;
};

export type KnowledgeAnalytics = {
  total_articles: number;
  published_articles: number;
  total_views: number;
  total_helpful: number;
  total_not_helpful: number;
  helpful_rate: number | null;
  total_faqs: number;
  total_searches: number;
  avg_results_per_search: number;
  zero_result_queries: number;
  unanswered_rate: number;
  top_articles: Record<string, unknown>[];
  need_improvement: Record<string, unknown>[];
  top_queries: Record<string, unknown>[];
  frequent_handover_topics: Record<string, number>;
};
