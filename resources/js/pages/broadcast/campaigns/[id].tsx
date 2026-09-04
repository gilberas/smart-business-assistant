import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  useCampaign,
  useRecipients,
  useCampaignAction,
  useDeleteCampaign,
} from '@/hooks/useBroadcast';
import { useBroadcastStore } from '@/stores/broadcast-store';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
  ArrowLeft,
  Play,
  Pause,
  XCircle,
  Copy,
  Trash2,
  Send,
  CheckCircle2,
  Clock,
  X as XIcon,
  MessageSquare,
  Users,
  Target,
} from 'lucide-react';

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
  scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  running: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  archived: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
  failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

export default function BroadcastCampaignDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const campaignId = Number(id);
  const { selectedCampaign: campaign, campaignStats: stats, isLoading } =
    useBroadcastStore();
  const [recipientPage, setRecipientPage] = useState(1);
  const [recipientStatusFilter, setRecipientStatusFilter] = useState<string>('all');
  const [deleteOpen, setDeleteOpen] = useState(false);

  const { refetch: refetchCampaign } = useCampaign(campaignId);
  const { data: recipientsData } = useRecipients(campaignId, {
    status: recipientStatusFilter !== 'all' ? recipientStatusFilter : undefined,
    page: recipientPage,
    per_page: 15,
  });

  const scheduleMutation = useCampaignAction('schedule');
  const pauseMutation = useCampaignAction('pause');
  const resumeMutation = useCampaignAction('resume');
  const cancelMutation = useCampaignAction('cancel');
  const duplicateMutation = useCampaignAction('duplicate');
  const deleteMutation = useDeleteCampaign();

  useEffect(() => {
    if (campaignId) refetchCampaign();
  }, [campaignId, refetchCampaign]);

  if (isLoading && !campaign) {
    return (
      <div className="space-y-6 p-6">
        <Skeleton className="h-8 w-64" />
        <div className="grid grid-cols-4 gap-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-64" />
      </div>
    );
  }

  if (!campaign) {
    return (
      <div className="p-6">
        <p className="text-muted-foreground">Campaign not found.</p>
        <Button variant="outline" onClick={() => navigate('/broadcast/campaigns')}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to Campaigns
        </Button>
      </div>
    );
  }

  const canSchedule =
    campaign.status === 'draft' || campaign.status === 'scheduled';
  const isRunning = campaign.status === 'running';
  const isPaused = campaign.status === 'paused';
  const isTerminal = ['completed', 'cancelled', 'archived', 'failed'].includes(
    campaign.status,
  );

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => navigate('/broadcast/campaigns')}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold">{campaign.name}</h1>
            <Badge
              variant="outline"
              className={statusColors[campaign.status] || ''}
            >
              {campaign.status_label}
            </Badge>
          </div>
          <p className="text-sm text-muted-foreground">
            Created{' '}
            {new Date(campaign.created_at).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
            })}
            {campaign.creator && ` by ${campaign.creator.name}`}
          </p>
        </div>
        <div className="flex gap-2">
          {canSchedule && (
            <Button
              onClick={() => scheduleMutation.mutate(campaign.id)}
              disabled={scheduleMutation.isPending}
            >
              <Send className="mr-2 h-4 w-4" />
              {campaign.status === 'scheduled' ? 'Send Now' : 'Schedule & Send'}
            </Button>
          )}
          {isRunning && (
            <Button
              variant="outline"
              onClick={() => pauseMutation.mutate(campaign.id)}
              disabled={pauseMutation.isPending}
            >
              <Pause className="mr-2 h-4 w-4" />
              Pause
            </Button>
          )}
          {isPaused && (
            <Button
              onClick={() => resumeMutation.mutate(campaign.id)}
              disabled={resumeMutation.isPending}
            >
              <Play className="mr-2 h-4 w-4" />
              Resume
            </Button>
          )}
          {!isTerminal && (
            <Button
              variant="outline"
              className="text-red-600"
              onClick={() => cancelMutation.mutate(campaign.id)}
              disabled={cancelMutation.isPending}
            >
              <XCircle className="mr-2 h-4 w-4" />
              Cancel
            </Button>
          )}
          <Button
            variant="outline"
            onClick={() => duplicateMutation.mutate(campaign.id)}
            disabled={duplicateMutation.isPending}
          >
            <Copy className="mr-2 h-4 w-4" />
            Duplicate
          </Button>
          <Button
            variant="outline"
            className="text-red-600"
            onClick={() => setDeleteOpen(true)}
          >
            <Trash2 className="mr-2 h-4 w-4" />
          </Button>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total ?? '-'}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Sent</CardTitle>
            <Send className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {stats ? stats.sent + stats.delivered + stats.read : '-'}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Delivered</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.delivered ?? '-'}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Failed</CardTitle>
            <XIcon className="h-4 w-4 text-red-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.failed ?? '-'}</div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">
              Delivery Rate
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">
              {stats ? `${stats.delivery_rate}%` : '-'}
            </div>
            <p className="text-xs text-muted-foreground">
              {stats?.sent ?? 0} sent / {stats?.total ?? 0} total
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Read Rate</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">
              {stats ? `${stats.read_rate}%` : '-'}
            </div>
            <p className="text-xs text-muted-foreground">
              {stats?.read ?? 0} read / {stats?.total ?? 0} total
            </p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="recipients">
        <TabsList>
          <TabsTrigger value="recipients">Recipients</TabsTrigger>
          <TabsTrigger value="details">Campaign Details</TabsTrigger>
        </TabsList>

        <TabsContent value="recipients" className="mt-4">
          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">Recipients</CardTitle>
                <select
                  className="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
                  value={recipientStatusFilter}
                  onChange={(e) => {
                    setRecipientStatusFilter(e.target.value);
                    setRecipientPage(1);
                  }}
                >
                  <option value="all">All statuses</option>
                  <option value="pending">Pending</option>
                  <option value="sent">Sent</option>
                  <option value="delivered">Delivered</option>
                  <option value="read">Read</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Customer</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Sent At</TableHead>
                    <TableHead>Error</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recipientsData?.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                        No recipients yet.
                      </TableCell>
                    </TableRow>
                  ) : (
                    recipientsData?.data.map((recipient) => (
                      <TableRow key={recipient.id}>
                        <TableCell className="font-medium">
                          {recipient.customer?.name || 'Unknown'}
                        </TableCell>
                        <TableCell className="text-muted-foreground">
                          {recipient.customer?.phone || '-'}
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline">{recipient.status}</Badge>
                        </TableCell>
                        <TableCell className="text-muted-foreground text-sm">
                          {recipient.sent_at
                            ? new Date(recipient.sent_at).toLocaleString()
                            : '-'}
                        </TableCell>
                        <TableCell className="text-red-600 text-sm max-w-[200px] truncate">
                          {recipient.error_message || '-'}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="details" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">
                Campaign Configuration
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    Template
                  </p>
                  <p>{campaign.template?.name || 'No template (direct message)'}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    Estimated Recipients
                  </p>
                  <p>{campaign.estimated_recipients ?? '-'}</p>
                </div>
                {campaign.scheduled_at && (
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">
                      Scheduled At
                    </p>
                    <p>
                      {new Date(campaign.scheduled_at).toLocaleString()}
                    </p>
                  </div>
                )}
                {campaign.started_at && (
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">
                      Started At
                    </p>
                    <p>
                      {new Date(campaign.started_at).toLocaleString()}
                    </p>
                  </div>
                )}
                {campaign.completed_at && (
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">
                      Completed At
                    </p>
                    <p>
                      {new Date(campaign.completed_at).toLocaleString()}
                    </p>
                  </div>
                )}
              </div>
              {!campaign.template && campaign.message_content && (
                <div>
                  <p className="text-sm font-medium text-muted-foreground mb-1">
                    Message Content
                  </p>
                  <div className="rounded-md bg-muted p-3 text-sm">
                    {campaign.message_content}
                  </div>
                </div>
              )}
              {campaign.recipient_filter && (
                <div>
                  <p className="text-sm font-medium text-muted-foreground mb-1">
                    Audience Filter
                  </p>
                  <pre className="rounded-md bg-muted p-3 text-xs overflow-auto">
                    {JSON.stringify(campaign.recipient_filter, null, 2)}
                  </pre>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Campaign</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete this campaign and all recipient
              records. This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              className="bg-red-600 hover:bg-red-700"
              onClick={() => {
                deleteMutation.mutate(campaign.id, {
                  onSuccess: () => navigate('/broadcast/campaigns'),
                });
              }}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
