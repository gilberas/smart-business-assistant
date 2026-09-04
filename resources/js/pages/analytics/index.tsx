import { useState } from 'react';
import { useOverview } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { ChartCard } from '@/components/analytics/ChartCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { MiniChart } from '@/components/analytics/MiniChart';
import { DoughnutChart } from '@/components/analytics/DoughnutChart';
import type { DatePreset } from '@/types/analytics';

export default function AnalyticsOverviewPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useOverview({ preset });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Overview</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {data?.kpis?.slice(0, 12).map((kpi) => (
          <KpiCard
            key={kpi.key}
            label={kpi.label}
            value={kpi.value}
            changePercent={kpi.change_percent}
            format={kpi.format}
            icon={kpi.icon}
            loading={isLoading}
          />
        ))}
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <ChartCard title="Conversation Trend" loading={isLoading}>
          {data?.conversation_trend && (
            <MiniChart
              labels={data.conversation_trend.labels}
              datasets={data.conversation_trend.datasets}
              height={240}
            />
          )}
        </ChartCard>

        <ChartCard title="Message Volume" loading={isLoading}>
          {data?.message_volume && (
            <MiniChart
              labels={data.message_volume.labels}
              datasets={data.message_volume.datasets}
              height={240}
            />
          )}
        </ChartCard>

        <ChartCard title="New Customers" loading={isLoading}>
          {data?.customer_trend && (
            <MiniChart
              labels={data.customer_trend.labels}
              datasets={data.customer_trend.datasets}
              height={240}
            />
          )}
        </ChartCard>

        <ChartCard title="AI Resolution Rate" loading={isLoading}>
          {data?.ai_performance && (
            <MiniChart
              labels={data.ai_performance.labels}
              datasets={data.ai_performance.datasets}
              format="percent"
              height={240}
            />
          )}
        </ChartCard>
      </div>
    </div>
  );
}
