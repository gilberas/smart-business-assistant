import { Outlet, useLocation, Link } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { MessageSquare, Users, Brain, UserCheck, Send, Workflow, BookOpen, BarChart3 } from 'lucide-react';

const navItems = [
  { href: '/analytics', label: 'Overview', icon: BarChart3 },
  { href: '/analytics/conversations', label: 'Conversations', icon: MessageSquare },
  { href: '/analytics/customers', label: 'Customers', icon: Users },
  { href: '/analytics/ai', label: 'AI Performance', icon: Brain },
  { href: '/analytics/agents', label: 'Human Agents', icon: UserCheck },
  { href: '/analytics/campaigns', label: 'Campaigns', icon: Send },
  { href: '/analytics/workflows', label: 'Workflows', icon: Workflow },
  { href: '/analytics/knowledge', label: 'Knowledge Base', icon: BookOpen },
];

export default function AnalyticsLayout() {
  const location = useLocation();

  return (
    <div className="space-y-6 p-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Analytics</h1>
        <p className="text-muted-foreground">Business intelligence and performance metrics</p>
      </div>

      <nav className="flex flex-wrap gap-1 border-b pb-px">
        {navItems.map((item) => {
          const active = location.pathname === item.href
            || (item.href !== '/analytics' && location.pathname.startsWith(item.href));
          return (
            <Link
              key={item.href}
              to={item.href}
              className={cn(
                'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-t-md border-b-2 border-transparent transition-colors',
                active
                  ? 'border-primary text-primary'
                  : 'text-muted-foreground hover:text-foreground hover:border-muted',
              )}
            >
              <item.icon className="h-4 w-4" />
              {item.label}
            </Link>
          );
        })}
      </nav>

      <Outlet />
    </div>
  );
}
