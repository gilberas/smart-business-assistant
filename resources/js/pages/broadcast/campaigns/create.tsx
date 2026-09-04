import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  useCreateCampaign,
  useActiveTemplates,
  useAudiencePreview,
} from '@/hooks/useBroadcast';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
  ArrowLeft,
  Users,
  MessageSquare,
  Calendar,
  Send,
} from 'lucide-react';
import { toast } from 'sonner';

type WizardStep = 'setup' | 'audience' | 'message' | 'review';

export default function BroadcastCampaignCreatePage() {
  const navigate = useNavigate();
  const createMutation = useCreateCampaign();
  const { data: activeTemplates, isLoading: templatesLoading } = useActiveTemplates();
  const previewMutation = useAudiencePreview();

  const [step, setStep] = useState<WizardStep>('setup');
  const [name, setName] = useState('');
  const [templateId, setTemplateId] = useState<string>('');
  const [messageContent, setMessageContent] = useState('');
  const [scheduledAt, setScheduledAt] = useState('');
  const [recipientFilter, setRecipientFilter] = useState<Record<string, unknown>>({});
  const [audienceCount, setAudienceCount] = useState<number | null>(null);
  const [creating, setCreating] = useState(false);

  const useTemplate = templateId !== 'none' && templateId !== '';

  const handleAudiencePreview = async () => {
    try {
      const result = await previewMutation.mutateAsync({
        filters: recipientFilter,
        limit: 10,
      });
      setAudienceCount(result.total_count);
    } catch {
      toast.error('Failed to preview audience');
    }
  };

  const handleCreate = async () => {
    setCreating(true);
    try {
      const payload = {
        name,
        template_id: useTemplate ? Number(templateId) : null,
        message_content: useTemplate ? null : messageContent || null,
        recipient_filter: recipientFilter,
        scheduled_at: scheduledAt || null,
        status: scheduledAt ? 'scheduled' : 'draft',
      };

      await createMutation.mutateAsync(payload);
      navigate('/broadcast/campaigns');
    } catch {
      // error handled by mutation
    } finally {
      setCreating(false);
    }
  };

  const isSetupValid = name.trim().length > 0;

  return (
    <div className="space-y-6 p-6 max-w-4xl mx-auto">
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => navigate('/broadcast/campaigns')}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">Create Broadcast Campaign</h1>
          <p className="text-sm text-muted-foreground">
            Step{' '}
            {step === 'setup'
              ? '1'
              : step === 'audience'
                ? '2'
                : step === 'message'
                  ? '3'
                  : '4'}{' '}
            of 4
          </p>
        </div>
      </div>

      <div className="flex gap-2 mb-6">
        {(['setup', 'audience', 'message', 'review'] as const).map((s, i) => (
          <div
            key={s}
            className={`flex-1 h-2 rounded-full ${
              step === s
                ? 'bg-primary'
                : ['setup', 'audience', 'message', 'review'].indexOf(step) > i
                  ? 'bg-primary/50'
                  : 'bg-muted'
            }`}
          />
        ))}
      </div>

      {step === 'setup' && (
        <Card>
          <CardHeader>
            <CardTitle>Campaign Basics</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Campaign Name *</Label>
              <Input
                id="name"
                placeholder="e.g., Summer Sale Announcement"
                value={name}
                onChange={(e) => setName(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="schedule">Schedule (optional)</Label>
              <div className="relative">
                <Calendar className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  id="schedule"
                  type="datetime-local"
                  className="pl-8"
                  value={scheduledAt}
                  onChange={(e) => setScheduledAt(e.target.value)}
                />
              </div>
              <p className="text-xs text-muted-foreground">
                Leave empty to save as draft and send later.
              </p>
            </div>
            <div className="flex justify-end pt-4">
              <Button onClick={() => setStep('audience')} disabled={!isSetupValid}>
                Next: Audience
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 'audience' && (
        <Card>
          <CardHeader>
            <CardTitle>Select Audience</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Filter by Tag (optional)</Label>
              <Input
                placeholder="Enter tag IDs as JSON array, e.g. [1,2,3]"
                onChange={(e) => {
                  try {
                    const val = e.target.value
                      ? JSON.parse(e.target.value)
                      : undefined;
                    setRecipientFilter((prev) => ({
                      ...prev,
                      tags: val ? { operator: 'in', value: val } : undefined,
                    }));
                  } catch {
                    // invalid JSON, ignore
                  }
                }}
              />
            </div>
            <div className="space-y-2">
              <Label>Filter by Status</Label>
              <Select
                onValueChange={(v) =>
                  setRecipientFilter((prev) => ({
                    ...prev,
                    status: v !== 'all' ? v : undefined,
                  }))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Any status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Any status</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="archived">Archived</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Filter by Lifecycle Stage</Label>
              <Select
                onValueChange={(v) =>
                  setRecipientFilter((prev) => ({
                    ...prev,
                    lifecycle_stage: v !== 'all' ? v : undefined,
                  }))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Any stage" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Any stage</SelectItem>
                  <SelectItem value="lead">Lead</SelectItem>
                  <SelectItem value="prospect">Prospect</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="vip">VIP</SelectItem>
                  <SelectItem value="at_risk">At Risk</SelectItem>
                  <SelectItem value="churned">Churned</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Filter by Loyalty Tier</Label>
              <Select
                onValueChange={(v) =>
                  setRecipientFilter((prev) => ({
                    ...prev,
                    loyalty_tier: v !== 'all' ? v : undefined,
                  }))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Any tier" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Any tier</SelectItem>
                  <SelectItem value="bronze">Bronze</SelectItem>
                  <SelectItem value="silver">Silver</SelectItem>
                  <SelectItem value="gold">Gold</SelectItem>
                  <SelectItem value="platinum">Platinum</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Button
              variant="outline"
              onClick={handleAudiencePreview}
              disabled={previewMutation.isPending}
            >
              <Users className="mr-2 h-4 w-4" />
              {previewMutation.isPending
                ? 'Counting...'
                : 'Preview Audience Size'}
            </Button>
            {audienceCount !== null && (
              <div className="rounded-md bg-muted p-3 flex items-center gap-2">
                <Users className="h-5 w-5 text-primary" />
                <span className="font-medium">
                  Estimated audience: {audienceCount.toLocaleString()} customers
                </span>
              </div>
            )}
            <div className="flex justify-between pt-4">
              <Button variant="outline" onClick={() => setStep('setup')}>
                Back
              </Button>
              <Button onClick={() => setStep('message')}>
                Next: Message
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 'message' && (
        <Card>
          <CardHeader>
            <CardTitle>Compose Message</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Send via Template (recommended)</Label>
              <Select value={templateId} onValueChange={setTemplateId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select a template..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No template (direct message)</SelectItem>
                  {templatesLoading ? (
                    <SelectItem value="loading" disabled>
                      Loading...
                    </SelectItem>
                  ) : (
                    activeTemplates?.map((t) => (
                      <SelectItem key={t.id} value={String(t.id)}>
                        {t.name} ({t.language})
                      </SelectItem>
                    ))
                  )}
                </SelectContent>
              </Select>
            </div>

            {!useTemplate && (
              <div className="space-y-2">
                <Label>Message Content</Label>
                <Textarea
                  placeholder="Enter your message. Use {{name}}, {{phone}}, {{email}} for personalization."
                  rows={6}
                  value={messageContent}
                  onChange={(e) => setMessageContent(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                  Available variables: {'{{name}}'} {'{{phone}}'} {'{{email}}'}{' '}
                  {'{{company}}'}
                </p>
              </div>
            )}

            {useTemplate && activeTemplates && (
              <div className="rounded-md bg-muted p-4">
                <p className="text-sm font-medium mb-2">
                  Template Preview: {activeTemplates.find((t) => String(t.id) === templateId)?.name}
                </p>
                <p className="text-sm whitespace-pre-wrap">
                  {activeTemplates.find((t) => String(t.id) === templateId)?.body}
                </p>
              </div>
            )}

            <div className="flex justify-between pt-4">
              <Button variant="outline" onClick={() => setStep('audience')}>
                Back
              </Button>
              <Button onClick={() => setStep('review')}>
                Next: Review
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 'review' && (
        <Card>
          <CardHeader>
            <CardTitle>Review & Confirm</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="rounded-lg border p-4 space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Name</span>
                <span className="text-sm font-medium">{name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Schedule</span>
                <span className="text-sm font-medium">
                  {scheduledAt
                    ? new Date(scheduledAt).toLocaleString()
                    : 'Send manually (draft)'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">
                  Estimated Audience
                </span>
                <span className="text-sm font-medium">
                  {audienceCount?.toLocaleString() ?? 'Not calculated'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">
                  Template
                </span>
                <span className="text-sm font-medium">
                  {useTemplate
                    ? activeTemplates?.find((t) => String(t.id) === templateId)
                        ?.name || 'Selected'
                    : 'Direct message'}
                </span>
              </div>
            </div>
            <div className="flex justify-between pt-4">
              <Button variant="outline" onClick={() => setStep('message')}>
                Back
              </Button>
              <Button onClick={handleCreate} disabled={creating}>
                <Send className="mr-2 h-4 w-4" />
                {creating
                  ? 'Creating...'
                  : scheduledAt
                    ? 'Schedule Campaign'
                    : 'Save as Draft'}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
