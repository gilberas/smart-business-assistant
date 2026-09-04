import { useState } from 'react';
import { useCustomerAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { ChartCard } from '@/components/analytics/ChartCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { MiniChart } from '@/components/analytics/MiniChart';
import { DoughnutChart } from '@/components/analytics/DoughnutChart';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DatePreset } from '@/types/analytics';

export default function CustomerAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useCustomerAnalytics({ preset });

  const lifecycleEntries = data?.by_lifecycle_stage ? Object.entries(data.by_lifecycle_stage) : [];
  const countryEntries = data?.by_country ? Object.entries(data.by_country) : [];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Customer Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Customers" value={data?.total ?? 0} loading={isLoading} icon="users" />
        <KpiCard label="New Customers" value={data?.new ?? 0} changePercent={data?.new_change_percent} loading={isLoading} icon="userPlus" />
        <KpiCard label="Interacted" value={data?.interacted ?? 0} loading={isLoading} />
        <KpiCard label="Returning" value={data?.returning ?? 0} loading={isLoading} />
        <KpiCard label="Inactive" value={data?.inactive ?? 0} loading={isLoading} />
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <ChartCard title="New Customers Trend" loading={isLoading}>
          {data?.trend && (
            <MiniChart labels={data.trend.labels} datasets={data.trend.datasets} height={220} />
          )}
        </ChartCard>

        <ChartCard title="Lifecycle Stage Distribution" loading={isLoading}>
          {lifecycleEntries.length > 0 ? (
            <DoughnutChart
              labels={lifecycleEntries.map(([k]) => k)}
              data={lifecycleEntries.map(([, v]) => v as number)}
              height={220}
            />
          ) : null}
        </ChartCard>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Customers by Country (Top 10)</CardTitle>
        </CardHeader>
        <CardContent>
          {countryEntries.length > 0 ? (
            <div className="space-y-2">
              {countryEntries.slice(0, 10).map(([country, count]) => {
                const maxCount = Math.max(...countryEntries.map(([, c]) => c as number));
                return (
                  <div key={country} className="flex items-center gap-2 text-sm">
                    <span className="w-32 truncate text-muted-foreground">{country || 'Unknown'}</span>
                    <div className="flex-1 h-2 rounded-full bg-muted">
                      <div
                        className="h-2 rounded-full bg-primary"
                        style={{ width: `${((count as number) / maxCount) * 100}%` }}
                      />
                    </div>
                    <span className="w-12 text-right font-medium">{count as number}</span>
                  </div>
                );
              })}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">No country data available</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
