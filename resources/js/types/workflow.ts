export type WorkflowStatus = 'draft' | 'active' | 'paused' | 'archived';
export type WorkflowExecutionMode = 'sequential' | 'parallel';
export type WorkflowRunStatus = 'pending' | 'running' | 'waiting' | 'completed' | 'failed' | 'cancelled' | 'retrying';
export type WorkflowTriggerType =
  | 'manual'
  | 'webhook'
  | 'scheduled'
  | 'api'
  | 'whatsapp_message'
  | 'conversation_assigned'
  | 'conversation_resolved'
  | 'customer_created'
  | 'customer_updated'
  | 'product_updated'
  | 'knowledge_article_published'
  | 'business_hours_started'
  | 'business_hours_ended';
export type WorkflowActionType =
  | 'send_ai_reply'
  | 'assign_conversation'
  | 'add_label'
  | 'remove_label'
  | 'add_internal_note'
  | 'update_customer'
  | 'create_task'
  | 'send_email'
  | 'send_whatsapp_template'
  | 'delay_execution'
  | 'call_webhook'
  | 'run_custom_action';
export type WorkflowConditionOperator =
  | 'equals'
  | 'not_equals'
  | 'contains'
  | 'not_contains'
  | 'starts_with'
  | 'ends_with'
  | 'regex'
  | 'date_before'
  | 'date_after'
  | 'date_on_or_before'
  | 'date_on_or_after'
  | 'greater_than'
  | 'greater_than_or_equal'
  | 'less_than'
  | 'less_than_or_equal'
  | 'is_true'
  | 'is_false'
  | 'is_empty'
  | 'is_not_empty'
  | 'in_array'
  | 'not_in_array'
  | 'has_tag'
  | 'not_has_tag'
  | 'business_hours'
  | 'ai_confidence_gte'
  | 'language_equals'
  | 'message_type';

export type WorkflowVariable = {
  id?: number;
  name: string;
  data_type: 'string' | 'number' | 'boolean' | 'array' | 'date';
  default_value?: unknown;
  description?: string;
  is_required?: boolean;
};

export type WorkflowTrigger = {
  id?: number;
  workflow_version_id?: number;
  type: WorkflowTriggerType;
  name: string;
  payload?: Record<string, unknown>;
  is_enabled?: boolean;
  conditions?: WorkflowCondition[];
};

export type WorkflowCondition = {
  id?: number;
  workflow_version_id?: number;
  workflow_trigger_id?: number | null;
  group_key?: string | null;
  field: string;
  operator: WorkflowConditionOperator;
  value?: unknown;
  boolean_connector?: 'and' | 'or';
  priority?: number;
};

export type WorkflowAction = {
  id?: number;
  workflow_version_id?: number;
  workflow_condition_id?: number | null;
  branch_key?: string | null;
  type: WorkflowActionType;
  name: string;
  payload?: Record<string, unknown>;
  position?: number;
  is_enabled?: boolean;
};

export type Workflow = {
  id: number;
  business_id: number;
  owner_id: number;
  name: string;
  description: string | null;
  category: string;
  status: WorkflowStatus;
  execution_mode: WorkflowExecutionMode;
  version: number;
  variables: WorkflowVariable[];
  metadata: Record<string, unknown>;
  enabled_at: string | null;
  created_at: string;
  updated_at: string;
  owner?: { id: number; name: string };
  latestVersion?: WorkflowVersion;
  versions?: WorkflowVersion[];
  runs?: WorkflowRun[];
  variablesDefinition?: WorkflowVariable[];
};

export type WorkflowVersion = {
  id: number;
  workflow_id: number;
  version: number;
  definition: Record<string, unknown>;
  created_by: number | null;
  change_summary: string | null;
  created_at: string;
  updated_at: string;
  triggers?: WorkflowTrigger[];
  conditions?: WorkflowCondition[];
  actions?: WorkflowAction[];
  creator?: { id: number; name: string };
};

export type WorkflowRun = {
  id: number;
  workflow_id: number;
  workflow_version_id: number | null;
  business_id: number;
  triggered_by: number | null;
  trigger_type: string;
  status: WorkflowRunStatus;
  execution_mode: string;
  attempts: number;
  max_attempts: number;
  input_payload: Record<string, unknown>;
  context: Record<string, unknown>;
  result: Record<string, unknown>;
  started_at: string | null;
  finished_at: string | null;
  next_retry_at: string | null;
  created_at: string;
  updated_at: string;
  logs?: WorkflowLog[];
  workflow?: Workflow;
  version?: WorkflowVersion;
};

export type WorkflowLog = {
  id: number;
  workflow_run_id: number;
  workflow_action_id: number | null;
  level: string;
  stage: string;
  message: string;
  payload: Record<string, unknown>;
  created_at: string;
  updated_at: string;
};

export type CreateWorkflowPayload = {
  name: string;
  description?: string;
  category: string;
  status?: WorkflowStatus;
  execution_mode?: WorkflowExecutionMode;
  variables?: WorkflowVariable[];
  metadata?: Record<string, unknown>;
  triggers?: WorkflowTrigger[];
  conditions?: WorkflowCondition[];
  actions?: WorkflowAction[];
};

export type UpdateWorkflowPayload = Partial<CreateWorkflowPayload>;

export type WorkflowFilters = {
  status?: WorkflowStatus;
  category?: string;
  search?: string;
  page?: number;
  per_page?: number;
};
