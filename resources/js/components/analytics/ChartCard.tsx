import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { ChartData } from '@/types/analytics';

interface ChartCardProps {
  title?: string;
  chartData?: ChartData;
  loading?: boolean;
  empty?: boolean;
  children?: React.ReactNode;
  className?: string;
}

export function ChartCard({ title, loading, empty, children, className }: ChartCardProps) {
  return (
    <Card className={className}>
      {title && (
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">{title}</CardTitle>
        </CardHeader>
      )}
      <CardContent>
        {loading ? (
          <div className="space-y-2">
            <Skeleton className="h-[200px] w-full rounded" />
          </div>
        ) : empty ? (
          <div className="flex h-[200px] items-center justify-center text-sm text-muted-foreground">
            No data available for this period
          </div>
        ) : (
          children
        )}
      </CardContent>
    </Card>
  );
}
