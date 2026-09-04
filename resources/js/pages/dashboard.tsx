import {
    MessageSquare,
    Users,
    Brain,
    BookOpen,
    Workflow,
    Send,
    ArrowUpRight,
    TrendingUp,
    Zap,
    Clock,
    CheckCircle,
    AlertTriangle,
    Bot,
    Phone,
} from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ChartCard } from '@/components/analytics/ChartCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { KpiCard } from '@/components/analytics/KpiCard';
import { MiniChart } from '@/components/analytics/MiniChart';
import { BusinessSwitcher } from '@/components/features/business/BusinessSwitcher';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useOverview } from '@/hooks/useAnalytics';
import { useBusinesses } from '@/hooks/useBusiness';
import { useBusinessStore } from '@/stores/business-store';
import type { DatePreset } from '@/types/analytics';
import { cn } from '@/lib/utils';

const quickActions = [
    {
        label: 'Conversations',
        href: '/analytics/conversations',
        icon: MessageSquare,
        color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        description: 'View chat history',
    },
    {
        label: 'Customers',
        href: '/customers',
        icon: Users,
        color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        description: 'Manage contacts',
    },
    {
        label: 'AI Performance',
        href: '/analytics/ai',
        icon: Brain,
        color: 'bg-teal-500/10 text-teal-600 dark:text-teal-400',
        description: 'View AI metrics',
    },
    {
        label: 'Campaigns',
        href: '/broadcast/campaigns',
        icon: Send,
        color: 'bg-pink-500/10 text-pink-600 dark:text-pink-400',
        description: 'Send broadcasts',
    },
    {
        label: 'Workflows',
        href: '/workflows',
        icon: Workflow,
        color: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
        description: 'Automation rules',
    },
    {
        label: 'Knowledge Base',
        href: '/rag/search',
        icon: BookOpen,
        color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        description: 'Manage content',
    },
];

function StatusDot({ status }: { status: 'connected' | 'disconnected' | 'error' }) {
    return (
        <span
            className={cn(
                'inline-block h-2 w-2 rounded-full',
                status === 'connected' && 'bg-emerald-500',
                status === 'disconnected' && 'bg-slate-400',
                status === 'error' && 'bg-red-500',
            )}
        />
    );
}

export default function DashboardPage() {
    const { currentBusiness, isLoading: bizLoading } = useBusinessStore();
    useBusinesses();
    const [preset, setPreset] = useState<DatePreset>('last_30_days');

    const { data: overview, isLoading: overviewLoading } = useOverview(
        { preset },
        !!currentBusiness,
    );

    if (bizLoading) {
        return (
            <div className="p-6 space-y-6">
                <Skeleton className="h-8 w-48" />
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {[1, 2, 3, 4].map((i) => (
                        <Skeleton key={i} className="h-32" />
                    ))}
                </div>
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-40" />
                    ))}
                </div>
            </div>
        );
    }

    if (!currentBusiness) {
        return (
            <div className="flex min-h-[60vh] items-center justify-center p-6">
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--primary)]/10">
                            <Brain className="h-6 w-6 text-[var(--primary)]" />
                        </div>
                        <CardTitle>Welcome!</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BusinessSwitcher />
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6">
            {currentBusiness.onboarding_step &&
                !currentBusiness.onboarding_completed_at && (
                    <Card className="border-[var(--primary)]/20 bg-[var(--primary)]/5">
                        <CardContent className="flex flex-col items-start justify-between gap-3 p-4 sm:flex-row sm:items-center">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--primary)]/10">
                                    <Zap className="h-5 w-5 text-[var(--primary)]" />
                                </div>
                                <div>
                                    <p className="font-medium">Finish setup</p>
                                    <p className="text-sm text-muted-foreground">
                                        Complete a few steps to get the most out of your
                                        assistant.
                                    </p>
                                </div>
                            </div>
                            <Button asChild variant="outline" size="sm">
                                <Link to={`/onboarding?business=${currentBusiness.id}`}>
                                    Continue
                                    <ArrowUpRight className="ml-1 h-3.5 w-3.5" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Good {new Date().getHours() < 12 ? 'morning' : new Date().getHours() < 18 ? 'afternoon' : 'evening'}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Here&apos;s what&apos;s happening with{' '}
                        <span className="font-medium text-foreground">
                            {currentBusiness.name}
                        </span>
                    </p>
                </div>
                <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
            </div>

            {overviewLoading ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {[1, 2, 3, 4].map((i) => (
                        <Skeleton key={i} className="h-28" />
                    ))}
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {overview?.kpis?.slice(0, 8).map((kpi) => (
                        <KpiCard
                            key={kpi.key}
                            label={kpi.label}
                            value={kpi.value}
                            changePercent={kpi.change_percent}
                            format={kpi.format}
                            icon={kpi.icon}
                        />
                    ))}
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card className="border-emerald-500/20 bg-gradient-to-br from-emerald-500/5 to-transparent">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Phone className="h-4 w-4 text-emerald-600" />
                                WhatsApp Status
                            </CardTitle>
                            <StatusDot status="connected" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Status</span>
                                <span className="font-medium text-emerald-600">Connected</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Phone</span>
                                <span className="font-medium">+255 700 000 000</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Messages today</span>
                                <span className="font-medium">42</span>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" className="mt-3 w-full" asChild>
                            <Link to="/whatsapp">
                                Manage
                                <ArrowUpRight className="ml-1 h-3.5 w-3.5" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card className="border-teal-500/20 bg-gradient-to-br from-teal-500/5 to-transparent">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Bot className="h-4 w-4 text-teal-600" />
                                AI Assistant
                            </CardTitle>
                            <StatusDot status="connected" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Resolution rate</span>
                                <span className="font-medium text-teal-600">87%</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Handled today</span>
                                <span className="font-medium">156</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Avg. response</span>
                                <span className="font-medium">1.2s</span>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" className="mt-3 w-full" asChild>
                            <Link to="/ai/settings">
                                Configure
                                <ArrowUpRight className="ml-1 h-3.5 w-3.5" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card className="border-blue-500/20 bg-gradient-to-br from-blue-500/5 to-transparent">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-blue-600" />
                                Needs Attention
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-start gap-2">
                                <AlertTriangle className="mt-0.5 h-4 w-4 text-amber-500 shrink-0" />
                                <p className="text-sm">
                                    <span className="font-medium">3 conversations</span>{' '}
                                    <span className="text-muted-foreground">awaiting human handoff</span>
                                </p>
                            </div>
                            <div className="flex items-start gap-2">
                                <Clock className="mt-0.5 h-4 w-4 text-orange-500 shrink-0" />
                                <p className="text-sm">
                                    <span className="font-medium">7 pending</span>{' '}
                                    <span className="text-muted-foreground">customer replies</span>
                                </p>
                            </div>
                            <div className="flex items-start gap-2">
                                <CheckCircle className="mt-0.5 h-4 w-4 text-emerald-500 shrink-0" />
                                <p className="text-sm">
                                    <span className="font-medium">92%</span>{' '}
                                    <span className="text-muted-foreground">resolved today</span>
                                </p>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" className="mt-3 w-full" asChild>
                            <Link to="/inbox">
                                View Inbox
                                <ArrowUpRight className="ml-1 h-3.5 w-3.5" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <ChartCard title="Conversation Trend" loading={overviewLoading}>
                    {overview?.conversation_trend && (
                        <MiniChart
                            labels={overview.conversation_trend.labels}
                            datasets={overview.conversation_trend.datasets}
                            height={220}
                        />
                    )}
                </ChartCard>

                <ChartCard title="Message Volume" loading={overviewLoading}>
                    {overview?.message_volume && (
                        <MiniChart
                            labels={overview.message_volume.labels}
                            datasets={overview.message_volume.datasets}
                            height={220}
                        />
                    )}
                </ChartCard>

                <ChartCard title="New Customers" loading={overviewLoading}>
                    {overview?.customer_trend && (
                        <MiniChart
                            labels={overview.customer_trend.labels}
                            datasets={overview.customer_trend.datasets}
                            height={220}
                        />
                    )}
                </ChartCard>

                <ChartCard title="AI Resolution Rate" loading={overviewLoading}>
                    {overview?.ai_performance && (
                        <MiniChart
                            labels={overview.ai_performance.labels}
                            datasets={overview.ai_performance.datasets}
                            height={220}
                        />
                    )}
                </ChartCard>
            </div>

            <div>
                <h2 className="text-lg font-semibold mb-3">Quick Actions</h2>
                <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    {quickActions.map((action) => (
                        <Link key={action.href} to={action.href}>
                            <Card className="cursor-pointer transition-all hover:shadow-md hover:border-[var(--primary)]/30 group">
                                <CardContent className="flex items-center gap-3 p-4">
                                    <div
                                        className={cn(
                                            'flex h-10 w-10 items-center justify-center rounded-lg transition-colors',
                                            action.color,
                                        )}
                                    >
                                        <action.icon className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium text-sm">{action.label}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {action.description}
                                        </p>
                                    </div>
                                    <ArrowUpRight className="h-4 w-4 text-muted-foreground group-hover:text-[var(--primary)] transition-colors" />
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </div>
    );
}
