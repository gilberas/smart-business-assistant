import { useState } from 'react';
import { useAgentAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import type { DatePreset } from '@/types/analytics';

export default function AgentAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useAgentAnalytics({ preset });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Human Agent Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Assigned" value={data?.total_assigned ?? 0} loading={isLoading} />
        <KpiCard label="Total Resolved" value={data?.total_resolved ?? 0} loading={isLoading} />
        <KpiCard label="Avg First Response" value={data?.avg_first_response_seconds ? `${Math.round(data.avg_first_response_seconds / 60)}m` : '-'} loading={isLoading} />
        <KpiCard label="Avg Resolution Time" value={data?.avg_resolution_minutes ? `${Math.round(data.avg_resolution_minutes)}m` : '-'} loading={isLoading} />
        <KpiCard label="Total Messages" value={data?.total_agent_messages ?? 0} loading={isLoading} icon="messageCircle" />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Agent Performance</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3].map((i) => <Skeleton key={i} className="h-8 w-full" />)}
            </div>
          ) : !data?.agent_stats || data.agent_stats.length === 0 ? (
            <p className="text-sm text-muted-foreground">No agent data available</p>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Agent</TableHead>
                    <TableHead className="text-right">Assigned</TableHead>
                    <TableHead className="text-right">Resolved</TableHead>
                    <TableHead className="text-right">Rate</TableHead>
                    <TableHead className="text-right">Messages</TableHead>
                    <TableHead className="text-right">Avg Response</TableHead>
                    <TableHead className="text-right">Handovers</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.agent_stats.map((agent) => (
                    <TableRow key={agent.agent_id}>
                      <TableCell className="font-medium">{agent.agent_name}</TableCell>
                      <TableCell className="text-right">{agent.conversations_assigned}</TableCell>
                      <TableCell className="text-right">{agent.conversations_resolved}</TableCell>
                      <TableCell className="text-right">{agent.resolution_rate}%</TableCell>
                      <TableCell className="text-right">{agent.messages_sent}</TableCell>
                      <TableCell className="text-right">
                        {agent.avg_first_response_seconds
                          ? `${Math.round(agent.avg_first_response_seconds / 60)}m`
                          : '-'}
                      </TableCell>
                      <TableCell className="text-right">{agent.handover_count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
