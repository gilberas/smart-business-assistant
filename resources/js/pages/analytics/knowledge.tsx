import { useState } from 'react';
import { useKnowledgeAnalytics } from '@/hooks/useAnalytics';
import { KpiCard } from '@/components/analytics/KpiCard';
import { DateRangePicker } from '@/components/analytics/DateRangePicker';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import type { DatePreset } from '@/types/analytics';

export default function KnowledgeAnalyticsPage() {
  const [preset, setPreset] = useState<DatePreset>('last_30_days');
  const { data, isLoading } = useKnowledgeAnalytics({ preset });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Knowledge Base Analytics</h2>
        <DateRangePicker value={preset} onChange={(p) => setPreset(p)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total Articles" value={data?.total_articles ?? 0} loading={isLoading} />
        <KpiCard label="Published" value={data?.published_articles ?? 0} loading={isLoading} />
        <KpiCard label="Total Views" value={data?.total_views ?? 0} loading={isLoading} />
        <KpiCard label="Helpful Rate" value={data?.helpful_rate ?? 0} format="percent" loading={isLoading} />
        <KpiCard label="Total FAQs" value={data?.total_faqs ?? 0} loading={isLoading} />
        <KpiCard label="Total Searches" value={data?.total_searches ?? 0} loading={isLoading} />
        <KpiCard label="Unanswered Rate" value={data?.unanswered_rate ?? 0} format="percent" loading={isLoading} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Top Viewed Articles</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <Skeleton className="h-32 w-full" />
          ) : !data?.top_articles || data.top_articles.length === 0 ? (
            <p className="text-sm text-muted-foreground">No article data available</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 pr-4">Article</th>
                    <th className="pb-2 pr-4 text-right">Views</th>
                    <th className="pb-2 pr-4 text-right">Helpful</th>
                    <th className="pb-2 text-right">Not Helpful</th>
                  </tr>
                </thead>
                <tbody>
                  {data.top_articles.map((a: any) => (
                    <tr key={a.id} className="border-b last:border-0">
                      <td className="py-2 pr-4 font-medium truncate max-w-[250px]">{a.title}</td>
                      <td className="py-2 pr-4 text-right">{a.view_count}</td>
                      <td className="py-2 pr-4 text-right text-green-600">{a.helpful_count}</td>
                      <td className="py-2 text-right text-red-600">{a.not_helpful_count}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {data?.need_improvement && data.need_improvement.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Articles Needing Improvement</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {data.need_improvement.slice(0, 10).map((a: any) => (
                <div key={a.id} className="flex items-center justify-between text-sm">
                  <span className="truncate max-w-[300px]">{a.title}</span>
                  <div className="flex gap-3 shrink-0">
                    <span className="text-green-600">+{a.helpful_count}</span>
                    <span className="text-red-600">-{a.not_helpful_count}</span>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {data?.frequent_handover_topics && Object.keys(data.frequent_handover_topics).length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Frequently Escalated Topics</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {Object.entries(data.frequent_handover_topics)
                .sort(([, a], [, b]) => b - a)
                .slice(0, 10)
                .map(([topic, count]) => (
                  <div key={topic} className="flex items-center gap-2 text-sm">
                    <span className="flex-1 truncate text-muted-foreground">{topic}</span>
                    <div className="w-24 h-2 rounded-full bg-muted">
                      <div
                        className="h-2 rounded-full bg-orange-500"
                        style={{ width: `${(count as number / Math.max(...Object.values(data.frequent_handover_topics))) * 100}%` }}
                      />
                    </div>
                    <span className="w-8 text-right font-medium">{count as number}</span>
                  </div>
                ))}
            </div>
          </CardContent>
        </Card>
      )}

      {data?.top_queries && data.top_queries.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Top Search Queries</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 pr-4">Query</th>
                    <th className="pb-2 pr-4 text-right">Searches</th>
                    <th className="pb-2 text-right">Avg Results</th>
                  </tr>
                </thead>
                <tbody>
                  {data.top_queries.slice(0, 20).map((q: any, i: number) => (
                    <tr key={i} className="border-b last:border-0">
                      <td className="py-2 pr-4 font-medium truncate max-w-[300px]">{q.query}</td>
                      <td className="py-2 pr-4 text-right">{q.count}</td>
                      <td className="py-2 text-right">{Math.round(q.avg_results || 0)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
