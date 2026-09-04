'use client';
import { useState } from 'react';
import { useAIUsage } from '@/hooks/useAI';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { DollarSign, MessagesSquare, Activity, AlertTriangle } from 'lucide-react';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

export default function AIUsagePage() {
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const { data: usageData, isLoading } = useAIUsage({ date_from: dateFrom, date_to: dateTo });

    const totals = usageData?.totals;
    const byProvider = usageData?.by_provider ?? [];
    const records = usageData?.data ?? [];

    const providerChartData = byProvider.map((p: { provider: string; total_requests: number; estimated_cost: number }) => ({
        name: p.provider,
        requests: p.total_requests,
        cost: p.estimated_cost,
    }));

    const dailyChartData = (Array.isArray(records) ? records : []).map((r: { date: string; total_requests: number }) => ({
        date: r.date,
        requests: r.total_requests,
    }));

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold">AI Usage Dashboard</h1>
                <p className="text-muted-foreground">Monitor AI usage, costs, and performance</p>
            </div>

            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <MessagesSquare className="size-4" />
                            Total Requests
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{totals?.total_requests ?? 0}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <Activity className="size-4" />
                            Total Tokens
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{(totals?.total_tokens ?? 0).toLocaleString()}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <DollarSign className="size-4" />
                            Estimated Cost
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">${(totals?.estimated_cost ?? 0).toFixed(4)}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <AlertTriangle className="size-4" />
                            Failed Requests
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-red-500">{totals?.failed_requests ?? 0}</div>
                    </CardContent>
                </Card>
            </div>

            <div className="flex gap-4">
                <div className="space-y-1">
                    <Label>From</Label>
                    <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                </div>
                <div className="space-y-1">
                    <Label>To</Label>
                    <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                </div>
                <div className="flex items-end">
                    <Button variant="outline" onClick={() => { setDateFrom(''); setDateTo(''); }}>
                        Clear
                    </Button>
                </div>
            </div>

            <Tabs defaultValue="overview">
                <TabsList>
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="providers">By Provider</TabsTrigger>
                    <TabsTrigger value="daily">Daily</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="space-y-4 pt-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Requests by Provider</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie data={providerChartData} dataKey="requests" nameKey="name" cx="50%" cy="50%" outerRadius={80} label>
                                            {providerChartData.map((_: unknown, i: number) => (
                                                <Cell key={i} fill={COLORS[i % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Provider Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Provider</TableHead>
                                            <TableHead className="text-right">Requests</TableHead>
                                            <TableHead className="text-right">Cost</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {byProvider.map((p: { provider: string; total_requests: number; estimated_cost: number }) => (
                                            <TableRow key={p.provider}>
                                                <TableCell className="font-medium">{p.provider}</TableCell>
                                                <TableCell className="text-right">{p.total_requests}</TableCell>
                                                <TableCell className="text-right">${p.estimated_cost.toFixed(4)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="providers" className="pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Cost by Provider</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={providerChartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="cost" fill="#0088FE" name="Cost ($)" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="daily" className="pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Requests</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={dailyChartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="date" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="requests" fill="#00C49F" name="Requests" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}
