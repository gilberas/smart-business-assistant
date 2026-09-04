import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { workflowApi } from '@/lib/api/workflow';
import type { CreateWorkflowPayload, WorkflowTrigger, WorkflowCondition, WorkflowAction, WorkflowVariable } from '@/types/workflow';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Plus, Trash2, GripVertical } from 'lucide-react';

const triggerTypes = [
  { value: 'manual', label: 'Manual trigger' },
  { value: 'scheduled', label: 'Scheduled (cron)' },
  { value: 'webhook', label: 'Webhook' },
  { value: 'whatsapp_message', label: 'New WhatsApp message' },
  { value: 'conversation_assigned', label: 'Conversation assigned' },
  { value: 'conversation_resolved', label: 'Conversation resolved' },
  { value: 'customer_created', label: 'Customer created' },
  { value: 'customer_updated', label: 'Customer updated' },
  { value: 'business_hours_started', label: 'Business hours started' },
  { value: 'business_hours_ended', label: 'Business hours ended' },
];

const actionTypes = [
  { value: 'send_ai_reply', label: 'Send AI reply' },
  { value: 'assign_conversation', label: 'Assign conversation' },
  { value: 'add_label', label: 'Add label' },
  { value: 'remove_label', label: 'Remove label' },
  { value: 'add_internal_note', label: 'Add internal note' },
  { value: 'update_customer', label: 'Update customer' },
  { value: 'create_task', label: 'Create task' },
  { value: 'send_email', label: 'Send email' },
  { value: 'send_whatsapp_template', label: 'Send WhatsApp template' },
  { value: 'delay_execution', label: 'Delay execution' },
  { value: 'call_webhook', label: 'Call webhook' },
  { value: 'run_custom_action', label: 'Custom action' },
];

const conditionOperators = [
  { value: 'equals', label: 'Equals' },
  { value: 'not_equals', label: 'Not equals' },
  { value: 'contains', label: 'Contains' },
  { value: 'starts_with', label: 'Starts with' },
  { value: 'ends_with', label: 'Ends with' },
  { value: 'regex', label: 'Regex' },
  { value: 'greater_than', label: 'Greater than' },
  { value: 'less_than', label: 'Less than' },
  { value: 'is_true', label: 'Is true' },
  { value: 'is_false', label: 'Is false' },
  { value: 'is_empty', label: 'Is empty' },
  { value: 'in_array', label: 'In array' },
  { value: 'has_tag', label: 'Has tag' },
  { value: 'business_hours', label: 'Business hours' },
  { value: 'language_equals', label: 'Language equals' },
];

export default function WorkflowBuilder() {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('details');
  const [saving, setSaving] = useState(false);

  const [form, setForm] = useState<CreateWorkflowPayload>({
    name: '',
    description: '',
    category: 'customer_service',
    status: 'draft',
    execution_mode: 'sequential',
    variables: [],
    triggers: [{ type: 'manual', name: 'Manual trigger', payload: {}, is_enabled: true }],
    conditions: [],
    actions: [{ type: 'add_internal_note', name: 'Add note', payload: { content: '' }, position: 0, is_enabled: true }],
  });

  function updateField<K extends keyof CreateWorkflowPayload>(key: K, value: CreateWorkflowPayload[K]) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  function addTrigger() {
    updateField('triggers', [...(form.triggers || []), { type: 'manual', name: 'New trigger', payload: {}, is_enabled: true }]);
  }

  function updateTrigger(index: number, field: string, value: unknown) {
    const triggers = [...(form.triggers || [])];
    triggers[index] = { ...triggers[index], [field]: value };
    updateField('triggers', triggers);
  }

  function removeTrigger(index: number) {
    updateField('triggers', (form.triggers || []).filter((_, i) => i !== index));
  }

  function addCondition() {
    updateField('conditions', [...(form.conditions || []), { field: '', operator: 'equals', value: '', group_key: null, boolean_connector: 'and' }]);
  }

  function updateCondition(index: number, field: string, value: unknown) {
    const conditions = [...(form.conditions || [])];
    conditions[index] = { ...conditions[index], [field]: value };
    updateField('conditions', conditions);
  }

  function removeCondition(index: number) {
    updateField('conditions', (form.conditions || []).filter((_, i) => i !== index));
  }

  function addAction() {
    updateField('actions', [...(form.actions || []), { type: 'add_internal_note', name: 'New action', payload: {}, position: (form.actions || []).length, is_enabled: true }]);
  }

  function updateAction(index: number, field: string, value: unknown) {
    const actions = [...(form.actions || [])];
    actions[index] = { ...actions[index], [field]: value };
    updateField('actions', actions);
  }

  function removeAction(index: number) {
    updateField('actions', (form.actions || []).filter((_, i) => i !== index));
  }

  function addVariable() {
    updateField('variables', [...(form.variables || []), { name: '', data_type: 'string', is_required: false }]);
  }

  function updateVariable(index: number, field: string, value: unknown) {
    const variables = [...(form.variables || [])];
    variables[index] = { ...variables[index], [field]: value };
    updateField('variables', variables);
  }

  function removeVariable(index: number) {
    updateField('variables', (form.variables || []).filter((_, i) => i !== index));
  }

  async function handleSave() {
    setSaving(true);
    try {
      await workflowApi.create(form as Record<string, unknown>);
      navigate('/workflows');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" onClick={() => navigate('/workflows')}>
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back
        </Button>
        <div className="flex-1">
          <h1 className="text-2xl font-bold">New Workflow</h1>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => navigate('/workflows')}>Cancel</Button>
          <Button onClick={handleSave} disabled={saving}>{saving ? 'Saving...' : 'Save Workflow'}</Button>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="details">Details</TabsTrigger>
          <TabsTrigger value="triggers">Triggers</TabsTrigger>
          <TabsTrigger value="conditions">Conditions</TabsTrigger>
          <TabsTrigger value="actions">Actions</TabsTrigger>
          <TabsTrigger value="variables">Variables</TabsTrigger>
        </TabsList>

        <TabsContent value="details" className="space-y-4 mt-4">
          <Card>
            <CardHeader><CardTitle>Workflow Details</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label>Name *</Label>
                <Input value={form.name} onChange={(e) => updateField('name', e.target.value)} placeholder="e.g., Welcome new customers" />
              </div>
              <div>
                <Label>Description</Label>
                <Textarea value={form.description || ''} onChange={(e) => updateField('description', e.target.value)} placeholder="What does this workflow do?" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label>Category</Label>
                  <Select value={form.category} onValueChange={(v) => updateField('category', v)}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="customer_service">Customer Service</SelectItem>
                      <SelectItem value="lead_management">Lead Management</SelectItem>
                      <SelectItem value="support">Support</SelectItem>
                      <SelectItem value="notifications">Notifications</SelectItem>
                      <SelectItem value="onboarding">Onboarding</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label>Execution Mode</Label>
                  <Select value={form.execution_mode} onValueChange={(v) => updateField('execution_mode', v as 'sequential' | 'parallel')}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="sequential">Sequential</SelectItem>
                      <SelectItem value="parallel">Parallel</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="triggers" className="space-y-4 mt-4">
          {form.triggers?.map((trigger, i) => (
            <Card key={i}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <CardTitle className="text-sm font-medium">Trigger {i + 1}</CardTitle>
                  <Button variant="ghost" size="sm" onClick={() => removeTrigger(i)}><Trash2 className="w-4 h-4" /></Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <Label>Type</Label>
                    <Select value={trigger.type} onValueChange={(v) => updateTrigger(i, 'type', v)}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {triggerTypes.map((t) => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Name</Label>
                    <Input value={trigger.name} onChange={(e) => updateTrigger(i, 'name', e.target.value)} />
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
          <Button variant="outline" onClick={addTrigger}><Plus className="w-4 h-4 mr-2" />Add Trigger</Button>
        </TabsContent>

        <TabsContent value="conditions" className="space-y-4 mt-4">
          {form.conditions?.map((condition, i) => (
            <Card key={i}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <CardTitle className="text-sm font-medium">Condition {i + 1}</CardTitle>
                  <Button variant="ghost" size="sm" onClick={() => removeCondition(i)}><Trash2 className="w-4 h-4" /></Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-3 gap-3">
                  <div>
                    <Label>Field</Label>
                    <Input value={condition.field} onChange={(e) => updateCondition(i, 'field', e.target.value)} placeholder="e.g., customer.language" />
                  </div>
                  <div>
                    <Label>Operator</Label>
                    <Select value={condition.operator} onValueChange={(v) => updateCondition(i, 'operator', v)}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {conditionOperators.map((op) => <SelectItem key={op.value} value={op.value}>{op.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Value</Label>
                    <Input value={String(condition.value || '')} onChange={(e) => updateCondition(i, 'value', e.target.value)} />
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
          <Button variant="outline" onClick={addCondition}><Plus className="w-4 h-4 mr-2" />Add Condition</Button>
        </TabsContent>

        <TabsContent value="actions" className="space-y-4 mt-4">
          {form.actions?.map((action, i) => (
            <Card key={i}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <GripVertical className="w-4 h-4 text-muted-foreground cursor-move" />
                    <CardTitle className="text-sm font-medium">Step {i + 1}</CardTitle>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => removeAction(i)}><Trash2 className="w-4 h-4" /></Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <Label>Type</Label>
                    <Select value={action.type} onValueChange={(v) => updateAction(i, 'type', v)}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {actionTypes.map((a) => <SelectItem key={a.value} value={a.value}>{a.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Name</Label>
                    <Input value={action.name} onChange={(e) => updateAction(i, 'name', e.target.value)} />
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
          <Button variant="outline" onClick={addAction}><Plus className="w-4 h-4 mr-2" />Add Action</Button>
        </TabsContent>

        <TabsContent value="variables" className="space-y-4 mt-4">
          {form.variables?.map((variable, i) => (
            <Card key={i}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <CardTitle className="text-sm font-medium">Variable {i + 1}</CardTitle>
                  <Button variant="ghost" size="sm" onClick={() => removeVariable(i)}><Trash2 className="w-4 h-4" /></Button>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-3 gap-3">
                  <div>
                    <Label>Name</Label>
                    <Input value={variable.name} onChange={(e) => updateVariable(i, 'name', e.target.value)} placeholder="variable_name" />
                  </div>
                  <div>
                    <Label>Type</Label>
                    <Select value={variable.data_type} onValueChange={(v) => updateVariable(i, 'data_type', v)}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="string">String</SelectItem>
                        <SelectItem value="number">Number</SelectItem>
                        <SelectItem value="boolean">Boolean</SelectItem>
                        <SelectItem value="array">Array</SelectItem>
                        <SelectItem value="date">Date</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Required</Label>
                    <Select value={String(variable.is_required)} onValueChange={(v) => updateVariable(i, 'is_required', v === 'true')}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="false">No</SelectItem>
                        <SelectItem value="true">Yes</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
          <Button variant="outline" onClick={addVariable}><Plus className="w-4 h-4 mr-2" />Add Variable</Button>
        </TabsContent>
      </Tabs>
    </div>
  );
}
