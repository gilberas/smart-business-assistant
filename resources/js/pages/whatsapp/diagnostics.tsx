'use client';

import {
    Activity,
    RefreshCw,
    CheckCircle2,
    XCircle,
    Phone,
    MessageSquare,
    Webhook,
    Shield,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    useWhatsAppDiagnostics,
    useWhatsAppAccount,
    useWhatsAppMessageStats,
    useWebhookHealth,
    useWebhookEvents,
} from '@/hooks/useWhatsApp';
import type { WebhookEvent } from '@/types/whatsapp';

function StatusCell({ status }: { status: string }) {
    const variant =
        status === 'processed'
            ? 'default'
            : status === 'pending'
              ? 'secondary'
              : status === 'failed'
                ? 'destructive'
                : 'outline';

    return <Badge variant={variant}>{status}</Badge>;
}

export default function DiagnosticsPage() {
    const { data: account, isLoading: accountLoading } = useWhatsAppAccount();
    const diagnostics = useWhatsAppDiagnostics();
    const { data: stats } = useWhatsAppMessageStats();
    const { data: webhookHealth } = useWebhookHealth();
    const { data: webhookEventsData } = useWebhookEvents({ per_page: 20 });

    const handleRefresh = () => {
        diagnostics.refetch();
        toast.success('Diagnostics refreshed');
    };

    const diagnosticsData = diagnostics.data;

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Link to="/whatsapp">
                        <Button variant="ghost" size="icon">
                            <Activity className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Diagnostics</h1>
                        <p className="text-muted-foreground">
                            WhatsApp connection health and diagnostics
                        </p>
                    </div>
                </div>
                <Button variant="outline" onClick={handleRefresh} disabled={diagnostics.isFetching}>
                    <RefreshCw
                        className={`mr-2 h-4 w-4 ${diagnostics.isFetching ? 'animate-spin' : ''}`}
                    />
                    Refresh
                </Button>
            </div>

            {accountLoading ? (
                <Skeleton className="h-64 w-full" />
            ) : !account ? (
                <Card>
                    <CardContent className="py-12 text-center">
                        <Activity className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                        <p>No WhatsApp account connected. Connect one to view diagnostics.</p>
                        <Link to="/whatsapp/connect">
                            <Button className="mt-4">Connect Account</Button>
                        </Link>
                    </CardContent>
                </Card>
            ) : (
                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="account">Account</TabsTrigger>
                        <TabsTrigger value="messages">Messages</TabsTrigger>
                        <TabsTrigger value="webhook">Webhook</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-sm">
                                        <CheckCircle2
                                            className={`h-4 w-4 ${account.status === 'connected' ? 'text-green-500' : 'text-red-500'}`}
                                        />
                                        Connection
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-lg font-semibold">{account.status}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-sm">
                                        <Shield className="h-4 w-4 text-blue-500" />
                                        Token
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Badge
                                        variant={
                                            account.token_status === 'active'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                    >
                                        {account.token_status}
                                    </Badge>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-sm">
                                        <Webhook className="h-4 w-4 text-purple-500" />
                                        Webhook
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {webhookHealth ? (
                                        <Badge
                                            variant={
                                                webhookHealth.connected ? 'default' : 'secondary'
                                            }
                                        >
                                            {webhookHealth.connected ? 'Active' : 'Inactive'}
                                        </Badge>
                                    ) : (
                                        <span className="text-sm text-muted-foreground">
                                            Unknown
                                        </span>
                                    )}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-sm">
                                        <MessageSquare className="h-4 w-4 text-orange-500" />
                                        Messages
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-lg font-semibold">
                                        {stats?.total ?? 0} total
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {diagnosticsData && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Detailed Diagnostics</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                                                Account
                                            </h3>
                                            <dl className="space-y-1 text-sm">
                                                <div className="flex justify-between">
                                                    <dt>WABA ID</dt>
                                                    <dd className="font-mono">
                                                        {diagnosticsData.account.waba_id ?? 'N/A'}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt>Connected</dt>
                                                    <dd>
                                                        {diagnosticsData.account.connected_at
                                                            ? new Date(
                                                                  diagnosticsData.account
                                                                      .connected_at,
                                                              ).toLocaleDateString()
                                                            : 'N/A'}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt>Last Synced</dt>
                                                    <dd>
                                                        {diagnosticsData.account.last_synced_at
                                                            ? new Date(
                                                                  diagnosticsData.account
                                                                      .last_synced_at,
                                                              ).toLocaleDateString()
                                                            : 'Never'}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                        <div>
                                            <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                                                Token
                                            </h3>
                                            <dl className="space-y-1 text-sm">
                                                <div className="flex justify-between">
                                                    <dt>Status</dt>
                                                    <dd>
                                                        <Badge
                                                            variant={
                                                                diagnosticsData.token.status ===
                                                                'active'
                                                                    ? 'default'
                                                                    : 'destructive'
                                                            }
                                                        >
                                                            {diagnosticsData.token.status}
                                                        </Badge>
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt>Days Until Expiry</dt>
                                                    <dd
                                                        className={
                                                            diagnosticsData.token.days_left !==
                                                                null &&
                                                            diagnosticsData.token.days_left < 14
                                                                ? 'font-semibold text-red-500'
                                                                : ''
                                                        }
                                                    >
                                                        {diagnosticsData.token.days_left !== null
                                                            ? `${diagnosticsData.token.days_left} days`
                                                            : 'Unknown'}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                    <Separator />
                                    <div>
                                        <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                                            Message Stats
                                        </h3>
                                        <div className="grid grid-cols-2 gap-2 md:grid-cols-4">
                                            {Object.entries(diagnosticsData.message_stats).map(
                                                ([key, value]) => (
                                                    <div
                                                        key={key}
                                                        className="rounded-lg border p-2 text-center"
                                                    >
                                                        <p className="text-xs text-muted-foreground capitalize">
                                                            {key}
                                                        </p>
                                                        <p className="text-lg font-semibold">
                                                            {value as number}
                                                        </p>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="account">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Phone className="h-5 w-5" />
                                    Phone Numbers
                                </CardTitle>
                                <CardDescription>
                                    {diagnosticsData?.phone_numbers
                                        ? `${diagnosticsData.phone_numbers.length} phone number(s)`
                                        : 'No phone number data'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {diagnosticsData?.phone_numbers &&
                                diagnosticsData.phone_numbers.length > 0 ? (
                                    <div className="space-y-2">
                                        {diagnosticsData.phone_numbers.map((pn) => (
                                            <div
                                                key={pn.id}
                                                className="flex items-center justify-between rounded-lg border p-3"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {pn.display_phone ?? pn.phone_number}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {pn.verified_name}
                                                    </p>
                                                    <p className="font-mono text-xs text-muted-foreground">
                                                        ID: {pn.phone_number_id}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            pn.status === 'active'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {pn.status}
                                                    </Badge>
                                                    {pn.quality_rating && (
                                                        <Badge
                                                            variant={
                                                                pn.quality_rating === 'GREEN'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {pn.quality_rating}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No phone numbers found. Sync your account.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="messages">
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Total</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">{stats?.total ?? 0}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Inbound</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">{stats?.inbound ?? 0}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Outbound</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">{stats?.outbound ?? 0}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Failed</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-red-500">
                                        {stats?.failed ?? 0}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Sent</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">{stats?.sent ?? 0}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Delivered</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-green-500">
                                        {stats?.delivered ?? 0}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Read</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-blue-500">
                                        {stats?.read ?? 0}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="webhook" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Webhook className="h-5 w-5" />
                                    Webhook Health
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {webhookHealth ? (
                                    <div className="grid gap-4 md:grid-cols-5">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Status</p>
                                            <Badge
                                                variant={
                                                    webhookHealth.connected
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {webhookHealth.connected ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Verified
                                            </p>
                                            <div className="flex items-center gap-1">
                                                {webhookHealth.webhook_verified ? (
                                                    <CheckCircle2 className="h-4 w-4 text-green-500" />
                                                ) : (
                                                    <XCircle className="h-4 w-4 text-red-500" />
                                                )}
                                                <span>
                                                    {webhookHealth.webhook_verified ? 'Yes' : 'No'}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Pending</p>
                                            <p className="text-lg font-semibold">
                                                {webhookHealth.pending_events}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Failed</p>
                                            <p className="text-lg font-semibold text-red-500">
                                                {webhookHealth.failed_events}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Dead Letters
                                            </p>
                                            <p className="text-lg font-semibold text-yellow-500">
                                                {webhookHealth.dead_letter_events}
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No webhook health data
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Webhook Events</CardTitle>
                                <CardDescription>
                                    Last {webhookEventsData?.data?.length ?? 0} events
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {webhookEventsData?.data && webhookEventsData.data.length > 0 ? (
                                    <div className="space-y-2">
                                        {webhookEventsData.data.map((event: WebhookEvent) => (
                                            <div
                                                key={event.id}
                                                className="flex items-center justify-between rounded-lg border p-3 text-sm"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <StatusCell status={event.status} />
                                                    <div>
                                                        <p className="font-medium">
                                                            {event.event_type}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {new Date(
                                                                event.created_at,
                                                            ).toLocaleString()}
                                                            {event.retry_count > 0 &&
                                                                ` (retry: ${event.retry_count})`}
                                                        </p>
                                                    </div>
                                                </div>
                                                {event.last_error && (
                                                    <p
                                                        className="max-w-xs truncate text-xs text-red-500"
                                                        title={event.last_error}
                                                    >
                                                        {event.last_error}
                                                    </p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No webhook events yet
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            )}
        </div>
    );
}
