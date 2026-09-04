import { Card, CardContent } from '@/components/ui/card';
import { cn, formatCurrency } from '@/lib/utils';
import {
  TrendingUp,
  TrendingDown,
  MessageSquare,
  Users,
  Bot,
  Headset,
  CheckCircle,
  Circle,
  Plus,
  UserPlus,
  Send,
  MessageCircle,
  Brain,
  ArrowRight,
  type LucideIcon,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
  conversation: MessageSquare,
  users: Users,
  userPlus: UserPlus,
  bot: Bot,
  headset: Headset,
  check: CheckCircle,
  circle: Circle,
  plus: Plus,
  send: Send,
  messageCircle: MessageCircle,
  brain: Brain,
  arrowRight: ArrowRight,
};

export interface KpiCardProps {
  label: string;
  value: number | string;
  changePercent?: number | null;
  format?: 'number' | 'percent' | 'currency' | 'duration';
  currency?: string;
  icon?: string | null;
  loading?: boolean;
}

export function KpiCard({ label, value, changePercent, format, currency, icon, loading }: KpiCardProps) {
  const Icon = icon ? iconMap[icon] : null;
  const isPositive = changePercent !== null && changePercent !== undefined && changePercent >= 0;
  const formattedValue = format === 'percent' ? `${value}%`
    : format === 'currency' ? formatCurrency(Number(value), currency ?? 'USD')
    : format === 'duration' ? `${value}m`
    : Number(value).toLocaleString();

  return (
    <Card>
      <CardContent className="p-4 sm:p-6">
        {loading ? (
          <div className="space-y-2">
            <div className="h-4 w-24 animate-pulse rounded bg-muted" />
            <div className="h-8 w-16 animate-pulse rounded bg-muted" />
            <div className="h-3 w-20 animate-pulse rounded bg-muted" />
          </div>
        ) : (
          <>
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-muted-foreground">{label}</p>
              {Icon && <Icon className="h-4 w-4 text-muted-foreground" />}
            </div>
            <div className="mt-2 flex items-baseline gap-2">
              <p className="text-2xl font-bold tracking-tight">{formattedValue}</p>
              {changePercent !== null && changePercent !== undefined && (
                <span
                  className={cn(
                    'inline-flex items-center gap-0.5 text-xs font-medium',
                    isPositive ? 'text-green-600' : 'text-red-600',
                  )}
                >
                  {isPositive ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
                  {Math.abs(changePercent).toFixed(1)}%
                </span>
              )}
            </div>
            {changePercent !== null && changePercent !== undefined && (
              <p className="mt-1 text-xs text-muted-foreground">vs previous period</p>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}
