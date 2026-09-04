import { useState } from 'react';
import { useWorkflowAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { ChartCard } from '@/components/analytics/ChartCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { DatePreset } from '@/types/analytics';

export default function WorkflowAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useWorkflowAnalytics({ preset });

  const mostUsed = data?.most_used_workflows ?? [];
  const maxUsed = Math.max(...mostUsed.map((w: any) => w.runs_count || 0), 1);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Workflow Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Runs" value={data?.total_runs ?? 0} loading={isLoading} />
        <KpiCard label="Successful" value={data?.successful ?? 0} loading={isLoading} />
        <KpiCard label="Failed" value={data?.failed ?? 0} loading={isLoading} />
        <KpiCard label="Success Rate" value={data?.success_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Avg Execution" value={data?.avg_execution_seconds ? `${Math.round(data.avg_execution_seconds)}s` : '-'} loading={isLoading} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Most Used Workflows</CardTitle>
        </CardHeader>
        <CardContent>
          {mostUsed.length > 0 ? (
            <div className="space-y-3">
              {mostUsed.slice(0, 10).map((w: any) => (
                <div key={w.id} className="flex items-center gap-2 text-sm">
                  <span className="flex-1 truncate">{w.name}</span>
                  <div className="w-32 h-2 rounded-full bg-muted">
                    <div
                      className="h-2 rounded-full bg-primary"
                      style={{ width: `${((w.runs_count || 0) / maxUsed) * 100}%` }}
                    />
                  </div>
                  <span className="w-12 text-right font-medium">{w.runs_count || 0}</span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">No workflow execution data</p>
          )}
        </CardContent>
      </Card>

      {data?.error_stages && Object.keys(data.error_stages).length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Common Error Stages</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {Object.entries(data.error_stages)
                .sort(([, a], [, b]) => b - a)
                .slice(0, 10)
                .map(([stage, count]) => (
                  <div key={stage} className="flex items-center gap-2 text-sm">
                    <span className="flex-1 truncate text-muted-foreground">{stage}</span>
                    <span className="font-medium text-red-600">{count}</span>
                  </div>
                ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
