import { useState } from 'react';
import { useAIAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { ChartCard } from '@/components/analytics/ChartCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { MiniChart } from '@/components/analytics/MiniChart';
import { DoughnutChart } from '@/components/analytics/DoughnutChart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DatePreset } from '@/types/analytics';

export default function AIAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useAIAnalytics({ preset });

  const providerEntries = data?.by_provider ? Object.entries(data.by_provider) : [];
  const modelEntries = data?.by_model ? Object.entries(data.by_model) : [];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">AI Performance Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total AI Requests" value={data?.total_requests ?? 0} loading={isLoading} icon="bot" />
        <KpiCard label="Success Rate" value={data?.success_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Failed Requests" value={data?.failed_requests ?? 0} loading={isLoading} />
        <KpiCard label="AI Resolution Rate" value={data?.ai_resolution_rate ?? 0} format="percent" loading={isLoading} icon="brain" />
        <KpiCard label="Handover Rate" value={data?.handover_rate ?? 0} format="percent" loading={isLoading} icon="arrowRight" />
        <KpiCard label="Avg Latency" value={data?.avg_latency_ms ?? 0} loading={isLoading} />
        <KpiCard label="Total Tokens" value={data?.total_tokens ?? 0} loading={isLoading} />
        <KpiCard label="Est. Cost" value={data?.estimated_cost ?? 0} format="currency" loading={isLoading} />
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <ChartCard title="AI Request Trend" loading={isLoading}>
          {data?.trend && (
            <MiniChart labels={data.trend.labels} datasets={data.trend.datasets} height={220} />
          )}
        </ChartCard>

        <ChartCard title="By Provider" loading={isLoading}>
          {providerEntries.length > 0 ? (
            <DoughnutChart
              labels={providerEntries.map(([k]) => k)}
              data={providerEntries.map(([, v]) => v as number)}
              height={220}
            />
          ) : null}
        </ChartCard>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Usage & Cost by Provider/Model</CardTitle>
        </CardHeader>
        <CardContent>
          {data?.usage_by_date && data.usage_by_date.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 pr-4">Provider</th>
                    <th className="pb-2 pr-4">Model</th>
                    <th className="pb-2 pr-4 text-right">Requests</th>
                    <th className="pb-2 pr-4 text-right">Tokens</th>
                    <th className="pb-2 text-right">Cost</th>
                  </tr>
                </thead>
                <tbody>
                  {data.usage_by_date.map((item: any, i: number) => (
                    <tr key={i} className="border-b last:border-0">
                      <td className="py-2 pr-4">{item.provider}</td>
                      <td className="py-2 pr-4 font-mono text-xs">{item.model}</td>
                      <td className="py-2 pr-4 text-right">{item.requests}</td>
                      <td className="py-2 pr-4 text-right">{Number(item.tokens).toLocaleString()}</td>
                      <td className="py-2 text-right">${Number(item.cost).toFixed(4)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">No usage data available</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
