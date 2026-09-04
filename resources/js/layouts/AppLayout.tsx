import {
    LayoutDashboard,
    Settings,
    Megaphone,
    FileText,
    BarChart3,
    MessageCircle,
    Brain,
    Sun,
    Moon,
    LogOut,
    User,
    Building2,
    ChevronLeft,
    ChevronRight,
    MessageSquare,
    Workflow,
    BookOpen,
    HelpCircle,
} from 'lucide-react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import { BusinessSwitcher } from '@/components/features/business/BusinessSwitcher';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarSeparator,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { useLogout } from '@/hooks/useAuth';
import { useAuthStore } from '@/stores/auth-store';
import { useContext } from 'react';
import { ThemeContext } from '@/providers/ThemeProvider';
import { cn } from '@/lib/utils';

const navItems = [
    {
        group: 'Overview',
        items: [
            { path: '/', label: 'Dashboard', icon: LayoutDashboard },
        ],
    },
    {
        group: 'Communication',
        items: [
            { path: '/inbox', label: 'Inbox', icon: MessageCircle },
            { path: '/customers', label: 'Customers', icon: MessageSquare },
            { path: '/broadcast/campaigns', label: 'Campaigns', icon: Megaphone },
            { path: '/broadcast/templates', label: 'Templates', icon: FileText },
        ],
    },
    {
        group: 'Intelligence',
        items: [
            { path: '/ai/settings', label: 'AI Assistant', icon: Brain },
            { path: '/workflows', label: 'Workflows', icon: Workflow },
            { path: '/rag/search', label: 'Knowledge Base', icon: BookOpen },
            { path: '/knowledge/faqs', label: 'FAQ Library', icon: HelpCircle },
        ],
    },
    {
        group: 'Analytics',
        items: [
            { path: '/analytics', label: 'Analytics', icon: BarChart3 },
        ],
    },
    {
        group: 'Configuration',
        items: [
            { path: '/whatsapp', label: 'WhatsApp', icon: MessageCircle },
            { path: '/business/settings', label: 'Business Settings', icon: Settings },
        ],
    },
];

function ThemeToggle() {
    const { theme, setTheme } = useContext(ThemeContext);

    return (
        <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
        >
            <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
            <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
            <span className="sr-only">Toggle theme</span>
        </Button>
    );
}

function AppSidebar() {
    const location = useLocation();
    const { state } = useSidebar();
    const { user } = useAuthStore();
    const logout = useLogout();
    const collapsed = state === 'collapsed';

    const initials = user?.name
        ?.split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <Sidebar
            collapsible="icon"
            className="border-r-0"
            style={{
                '--sidebar-background': 'var(--sidebar)',
                '--sidebar-foreground': 'var(--sidebar-foreground)',
                '--sidebar-primary': 'var(--sidebar-primary)',
                '--sidebar-primary-foreground': 'var(--sidebar-primary-foreground)',
                '--sidebar-accent': 'var(--sidebar-accent)',
                '--sidebar-accent-foreground': 'var(--sidebar-accent-foreground)',
                '--sidebar-border': 'var(--sidebar-border)',
                '--sidebar-ring': 'var(--sidebar-ring)',
            } as React.CSSProperties}
        >
            <SidebarHeader className="p-3">
                <Link to="/" className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--sidebar-primary)]">
                        <Building2 className="h-4 w-4 text-white" />
                    </div>
                    {!collapsed && (
                        <span className="text-sm font-semibold text-white">
                            Business Assistant
                        </span>
                    )}
                </Link>
                {!collapsed && (
                    <div className="mt-2">
                        <BusinessSwitcher />
                    </div>
                )}
            </SidebarHeader>

            <SidebarContent>
                {navItems.map((group) => (
                    <SidebarGroup key={group.group}>
                        <SidebarGroupLabel className="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            {!collapsed && group.group}
                        </SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {group.items.map((item) => {
                                    const isActive =
                                        item.path === '/'
                                            ? location.pathname === '/'
                                            : location.pathname.startsWith(item.path);

                                    return (
                                        <SidebarMenuItem key={item.path}>
                                            <SidebarMenuButton
                                                asChild
                                                isActive={isActive}
                                                className={cn(
                                                    'text-slate-400 hover:text-white',
                                                    isActive &&
                                                        'bg-[var(--sidebar-accent)] text-white',
                                                )}
                                                tooltip={item.label}
                                            >
                                                <Link to={item.path}>
                                                    <item.icon className="h-4 w-4" />
                                                    <span>{item.label}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                            {item.path === '/inbox' && (
                                                <SidebarMenuBadge className="bg-[var(--sidebar-primary)] text-white text-[10px] font-bold">
                                                    3
                                                </SidebarMenuBadge>
                                            )}
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarSeparator />
            <SidebarFooter className="p-3">
                {!collapsed ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button className="flex w-full items-center gap-2 rounded-lg p-2 text-left text-sm text-slate-300 hover:bg-[var(--sidebar-accent)] hover:text-white transition-colors">
                                <Avatar className="h-8 w-8 border-2 border-[var(--sidebar-primary)]">
                                    <AvatarImage src={user?.avatar || undefined} />
                                    <AvatarFallback className="bg-[var(--sidebar-primary)] text-white text-xs font-bold">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1 min-w-0">
                                    <p className="truncate font-medium text-white text-sm">{user?.name}</p>
                                    <p className="truncate text-xs text-slate-500">{user?.email}</p>
                                </div>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-56">
                            <DropdownMenuItem asChild>
                                <Link to="/profile">
                                    <User className="mr-2 h-4 w-4" />
                                    Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => logout.mutate()}
                                disabled={logout.isPending}
                                className="text-destructive"
                            >
                                <LogOut className="mr-2 h-4 w-4" />
                                Sign out
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-white">
                                <Avatar className="h-7 w-7 border-2 border-[var(--sidebar-primary)]">
                                    <AvatarImage src={user?.avatar || undefined} />
                                    <AvatarFallback className="bg-[var(--sidebar-primary)] text-white text-[10px] font-bold">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-56">
                            <div className="px-2 py-1.5">
                                <p className="text-sm font-medium">{user?.name}</p>
                                <p className="text-xs text-muted-foreground">{user?.email}</p>
                            </div>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                                <Link to="/profile">
                                    <User className="mr-2 h-4 w-4" />
                                    Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => logout.mutate()}
                                disabled={logout.isPending}
                                className="text-destructive"
                            >
                                <LogOut className="mr-2 h-4 w-4" />
                                Sign out
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </SidebarFooter>
        </Sidebar>
    );
}

export default function AppLayout() {
    const location = useLocation();

    const getPageTitle = () => {
        const path = location.pathname;
        if (path === '/') return 'Dashboard';
        if (path.startsWith('/inbox')) return 'Inbox';
        if (path.startsWith('/customers')) return 'Customers';
        if (path.startsWith('/broadcast/campaigns')) return 'Campaigns';
        if (path.startsWith('/broadcast/templates')) return 'Templates';
        if (path.startsWith('/ai')) return 'AI Assistant';
        if (path.startsWith('/workflows')) return 'Workflows';
        if (path.startsWith('/rag')) return 'Knowledge Base';
        if (path.startsWith('/analytics')) return 'Analytics';
        if (path.startsWith('/whatsapp')) return 'WhatsApp';
        if (path.startsWith('/business/settings')) return 'Business Settings';
        if (path.startsWith('/profile')) return 'Profile';
        return 'Business Assistant';
    };

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="sticky top-0 z-40 flex h-14 items-center gap-3 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 px-4">
                    <SidebarTrigger className="text-muted-foreground hover:text-foreground" />
                    <Separator orientation="vertical" className="h-6" />
                    <h1 className="text-sm font-semibold">{getPageTitle()}</h1>
                    <div className="ml-auto flex items-center gap-1">
                        <ThemeToggle />
                    </div>
                </header>
                <div className="flex-1">
                    <Outlet />
                </div>
            </SidebarInset>
        </SidebarProvider>
    );
}
