'use client';
import { Activity, CheckCircle, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAIHealth, useAvailableProviders } from '@/hooks/useAI';

export default function AIHealthPage() {
    const { data: healthData } = useAIHealth();
    const { data: providersData } = useAvailableProviders();

    const health = healthData?.data ?? {};
    const providers = providersData?.data ?? {};

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div className="flex items-center gap-3">
                <Activity className="size-6" />
                <div>
                    <h1 className="text-2xl font-bold">Provider Health</h1>
                    <p className="text-muted-foreground">Real-time status of AI providers</p>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {Object.entries(health).map(
                    ([slug, info]: [string, { healthy: boolean; name: string }]) => (
                        <Card key={slug}>
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between text-sm">
                                    {info.name}
                                    {info.healthy ? (
                                        <CheckCircle className="size-5 text-green-500" />
                                    ) : (
                                        <XCircle className="size-5 text-red-500" />
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Badge variant={info.healthy ? 'default' : 'destructive'}>
                                    {info.healthy ? 'Operational' : 'Down'}
                                </Badge>
                                <div className="mt-2 text-xs text-muted-foreground">
                                    Models: {providers[slug]?.models?.length ?? 0}
                                </div>
                            </CardContent>
                        </Card>
                    ),
                )}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Provider Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Provider</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Available Models</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {Object.entries(providers).map(
                                ([slug, info]: [
                                    string,
                                    {
                                        name: string;
                                        healthy: boolean;
                                        models: { id: string; name: string; owned_by?: string }[];
                                    },
                                ]) => (
                                    <TableRow key={slug}>
                                        <TableCell className="font-medium">{info.name}</TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={info.healthy ? 'default' : 'destructive'}
                                            >
                                                {info.healthy ? 'Healthy' : 'Unhealthy'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{info.models?.length ?? 0}</TableCell>
                                    </TableRow>
                                ),
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
