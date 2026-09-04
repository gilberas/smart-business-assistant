'use client';

import {
    Phone,
    MessageSquare,
    Image,
    Webhook,
    Activity,
    RefreshCw,
    Plug,
    Unplug,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    Clock,
} from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    useWhatsAppAccount,
    useDisconnectWhatsApp,
    useSyncPhoneNumbers,
    useWhatsAppMessageStats,
    useWebhookHealth,
} from '@/hooks/useWhatsApp';

const statusConfig: Record<string, { color: string; icon: typeof CheckCircle2 }> = {
    connected: { color: 'text-green-500', icon: CheckCircle2 },
    disconnected: { color: 'text-gray-400', icon: XCircle },
    error: { color: 'text-red-500', icon: AlertTriangle },
    expired: { color: 'text-yellow-500', icon: Clock },
};

export default function WhatsAppSettingsPage() {
    const { data: account, isLoading: accountLoading } = useWhatsAppAccount();
    const { data: stats } = useWhatsAppMessageStats();
    const { data: webhookHealth } = useWebhookHealth();
    const disconnectMutation = useDisconnectWhatsApp();
    const syncMutation = useSyncPhoneNumbers();
    const [disconnectDialog, setDisconnectDialog] = useState(false);

    const StatusIcon = account
        ? (statusConfig[account.status]?.icon ?? CheckCircle2)
        : CheckCircle2;

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">WhatsApp Integration</h1>
                    <p className="text-muted-foreground">
                        Manage your WhatsApp Business Account connection
                    </p>
                </div>
                <div className="flex gap-2">
                    {!account && (
                        <Link to="/whatsapp/connect">
                            <Button>
                                <Plug className="mr-2 h-4 w-4" />
                                Connect Account
                            </Button>
                        </Link>
                    )}
                    {account?.status === 'connected' && (
                        <>
                            <Link to="/whatsapp/diagnostics">
                                <Button variant="outline">
                                    <Activity className="mr-2 h-4 w-4" />
                                    Diagnostics
                                </Button>
                            </Link>
                            <Button variant="destructive" onClick={() => setDisconnectDialog(true)}>
                                <Unplug className="mr-2 h-4 w-4" />
                                Disconnect
                            </Button>
                        </>
                    )}
                </div>
            </div>

            {accountLoading ? (
                <div className="space-y-4">
                    <Skeleton className="h-32 w-full" />
                    <Skeleton className="h-48 w-full" />
                </div>
            ) : account ? (
                <>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <StatusIcon
                                    className={`h-5 w-5 ${statusConfig[account.status]?.color ?? ''}`}
                                />
                                Account Status
                                <Badge
                                    variant={
                                        account.status === 'connected' ? 'default' : 'secondary'
                                    }
                                >
                                    {account.status}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">WABA ID</p>
                                <p className="font-mono text-sm">
                                    {account.waba_id ?? 'Not configured'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Connected Since</p>
                                <p>
                                    {account.connected_at
                                        ? new Date(account.connected_at).toLocaleDateString()
                                        : 'N/A'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Last Synced</p>
                                <p>
                                    {account.last_synced_at
                                        ? new Date(account.last_synced_at).toLocaleDateString()
                                        : 'Never'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Token Status</p>
                                <Badge
                                    variant={
                                        account.token_status === 'active'
                                            ? 'default'
                                            : 'destructive'
                                    }
                                >
                                    {account.token_status}
                                </Badge>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Webhook Verified</p>
                                <div className="flex items-center gap-2">
                                    {account.webhook_verified ? (
                                        <CheckCircle2 className="h-4 w-4 text-green-500" />
                                    ) : (
                                        <XCircle className="h-4 w-4 text-red-500" />
                                    )}
                                    <span>{account.webhook_verified ? 'Yes' : 'No'}</span>
                                </div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Last Webhook Received
                                </p>
                                <p>
                                    {account.last_webhook_received_at
                                        ? new Date(
                                              account.last_webhook_received_at,
                                          ).toLocaleString()
                                        : 'Never'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Messages
                                </CardTitle>
                                <MessageSquare className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats?.total ?? 0}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">Inbound</CardTitle>
                                <Phone className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats?.inbound ?? 0}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">Outbound</CardTitle>
                                <Image className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats?.outbound ?? 0}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">Delivered</CardTitle>
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats?.delivered ?? 0}</div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <Webhook className="h-5 w-5" />
                                    Webhook Status
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => syncMutation.mutate()}
                                    disabled={syncMutation.isPending}
                                >
                                    <RefreshCw
                                        className={`mr-2 h-4 w-4 ${syncMutation.isPending ? 'animate-spin' : ''}`}
                                    />
                                    Sync Phone Numbers
                                </Button>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {webhookHealth ? (
                                <div className="grid gap-4 md:grid-cols-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Status</p>
                                        <Badge
                                            variant={
                                                webhookHealth.connected ? 'default' : 'secondary'
                                            }
                                        >
                                            {webhookHealth.connected ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Pending Events
                                        </p>
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
                                    Webhook health data unavailable
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {account.phone_numbers && account.phone_numbers.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Phone className="h-5 w-5" />
                                    Phone Numbers
                                </CardTitle>
                                <CardDescription>
                                    {account.phone_numbers.length} number(s) connected
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {account.phone_numbers.map((pn) => (
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
                                                    <Badge variant="outline">
                                                        {pn.quality_rating}
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </>
            ) : (
                <Card>
                    <CardContent className="flex flex-col items-center justify-center py-12">
                        <Plug className="mb-4 h-12 w-12 text-muted-foreground" />
                        <h2 className="mb-2 text-xl font-semibold">
                            No WhatsApp Account Connected
                        </h2>
                        <p className="mb-6 text-center text-muted-foreground">
                            Connect your Meta WhatsApp Business Account to start sending and
                            receiving messages.
                        </p>
                        <Link to="/whatsapp/connect">
                            <Button size="lg">
                                <Plug className="mr-2 h-5 w-5" />
                                Connect Now
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            )}

            <Dialog open={disconnectDialog} onOpenChange={setDisconnectDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Disconnect WhatsApp Account</DialogTitle>
                        <DialogDescription>
                            This will disconnect your WhatsApp Business Account. You will no longer
                            receive webhook events until you reconnect. Messages in the system will
                            be preserved.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDisconnectDialog(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                disconnectMutation.mutate(undefined, {
                                    onSuccess: () => {
                                        toast.success('WhatsApp account disconnected');
                                        setDisconnectDialog(false);
                                    },
                                    onError: () => toast.error('Failed to disconnect'),
                                });
                            }}
                            disabled={disconnectMutation.isPending}
                        >
                            {disconnectMutation.isPending ? (
                                <Spinner className="mr-2" />
                            ) : (
                                <Unplug className="mr-2 h-4 w-4" />
                            )}
                            Disconnect
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
