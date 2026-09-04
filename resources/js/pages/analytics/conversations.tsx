import { useState } from 'react';
import { useConversationAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { ChartCard } from '@/components/analytics/ChartCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { FilterBar } from '@/components/analytics/FilterBar';
import { MiniChart } from '@/components/analytics/MiniChart';
import { DoughnutChart } from '@/components/analytics/DoughnutChart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DatePreset } from '@/types/analytics';

export default function ConversationAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const [mode, setMode] = useState('all');
  const { data, isLoading } = useConversationAnalytics({
    preset,
    mode: mode !== 'all' ? mode as 'ai' | 'human' : undefined,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Conversation Analytics</h2>
        <div className="flex gap-2">
          <FilterBar mode={mode} onModeChange={setMode} />
          <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Conversations" value={data?.total ?? 0} changePercent={data?.total_change_percent} loading={isLoading} icon="conversation" />
        <KpiCard label="Open" value={data?.open ?? 0} loading={isLoading} icon="circle" />
        <KpiCard label="Closed / Resolved" value={data?.closed ?? 0} loading={isLoading} icon="check" />
        <KpiCard label="Reopened" value={data?.reopened ?? 0} loading={isLoading} />
        <KpiCard label="AI Handled" value={data?.ai_handled ?? 0} loading={isLoading} icon="bot" />
        <KpiCard label="Human Handled" value={data?.human_handled ?? 0} loading={isLoading} icon="headset" />
        <KpiCard label="Handover Rate" value={data?.handover_rate ?? 0} format="percent" loading={isLoading} icon="arrowRight" />
        <KpiCard label="Resolution Rate" value={data?.resolution_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Avg Duration" value={data?.avg_duration_minutes ?? 0} format="duration" loading={isLoading} />
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <ChartCard title="Conversation Trend" loading={isLoading}>
          {data?.trend && (
            <MiniChart labels={data.trend.labels} datasets={data.trend.datasets} height={220} />
          )}
        </ChartCard>

        <ChartCard title="Status Breakdown" loading={isLoading}>
          {data?.breakdown && (
            <DoughnutChart
              labels={data.breakdown.labels}
              data={data.breakdown.datasets[0]?.data || []}
              height={220}
            />
          )}
        </ChartCard>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Peak Hours</CardTitle>
          </CardHeader>
          <CardContent>
            {data?.peak_hours && Object.keys(data.peak_hours).length > 0 ? (
              <div className="space-y-2">
                {Object.entries(data.peak_hours)
                  .sort(([, a], [, b]) => b - a)
                  .slice(0, 5)
                  .map(([hour, count]) => (
                    <div key={hour} className="flex items-center gap-2 text-sm">
                      <span className="w-12 text-muted-foreground">{hour}:00</span>
                      <div className="flex-1 h-2 rounded-full bg-muted">
                        <div
                          className="h-2 rounded-full bg-primary"
                          style={{ width: `${Math.min((count / Math.max(...Object.values(data.peak_hours))) * 100, 100)}%` }}
                        />
                      </div>
                      <span className="w-12 text-right font-medium">{count}</span>
                    </div>
                  ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Peak Days</CardTitle>
          </CardHeader>
          <CardContent>
            {data?.peak_days && Object.keys(data.peak_days).length > 0 ? (
              <div className="space-y-2">
                {Object.entries(data.peak_days)
                  .sort(([, a], [, b]) => b - a)
                  .map(([day, count]) => (
                    <div key={day} className="flex items-center gap-2 text-sm">
                      <span className="w-24 text-muted-foreground">{day}</span>
                      <div className="flex-1 h-2 rounded-full bg-muted">
                        <div
                          className="h-2 rounded-full bg-primary"
                          style={{ width: `${Math.min((count / Math.max(...Object.values(data.peak_days))) * 100, 100)}%` }}
                        />
                      </div>
                      <span className="w-12 text-right font-medium">{count}</span>
                    </div>
                  ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
