import { useState } from 'react';
import { useCampaignAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import type { DatePreset } from '@/types/analytics';

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800',
  completed: 'bg-green-100 text-green-800',
  running: 'bg-blue-100 text-blue-800',
  failed: 'bg-red-100 text-red-800',
  scheduled: 'bg-yellow-100 text-yellow-800',
};

export default function CampaignAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useCampaignAnalytics({ preset });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Campaign Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Campaigns" value={data?.total_campaigns ?? 0} loading={isLoading} />
        <KpiCard label="Sent" value={data?.sent ?? 0} loading={isLoading} />
        <KpiCard label="Completed" value={data?.completed ?? 0} loading={isLoading} />
        <KpiCard label="Failed" value={data?.failed_campaigns ?? 0} loading={isLoading} />
        <KpiCard label="Total Recipients" value={data?.total_recipients ?? 0} loading={isLoading} />
        <KpiCard label="Delivery Rate" value={data?.delivery_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Read Rate" value={data?.read_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Failure Rate" value={data?.failure_rate ?? 0} format="percent" loading={isLoading} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Campaign Performance</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3].map((i) => <Skeleton key={i} className="h-8 w-full" />)}
            </div>
          ) : !data?.campaign_performance || data.campaign_performance.length === 0 ? (
            <p className="text-sm text-muted-foreground">No campaign data available</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 pr-4">Campaign</th>
                    <th className="pb-2 pr-4">Status</th>
                    <th className="pb-2 pr-4 text-right">Recipients</th>
                    <th className="pb-2 pr-4 text-right">Sent</th>
                    <th className="pb-2 pr-4 text-right">Delivered</th>
                    <th className="pb-2 pr-4 text-right">Read</th>
                    <th className="pb-2 text-right">Failed</th>
                  </tr>
                </thead>
                <tbody>
                  {data.campaign_performance.map((c: any) => (
                    <tr key={c.id} className="border-b last:border-0">
                      <td className="py-2 pr-4 font-medium truncate max-w-[200px]">{c.name}</td>
                      <td className="py-2 pr-4">
                        <Badge variant="outline" className={statusColors[c.status] || ''}>
                          {c.status}
                        </Badge>
                      </td>
                      <td className="py-2 pr-4 text-right">{c.total_recipients || 0}</td>
                      <td className="py-2 pr-4 text-right">{c.sent_recipients || 0}</td>
                      <td className="py-2 pr-4 text-right">{c.delivered_recipients || 0}</td>
                      <td className="py-2 pr-4 text-right">{c.read_recipients || 0}</td>
                      <td className="py-2 text-right text-red-600">{c.failed_recipients || 0}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
