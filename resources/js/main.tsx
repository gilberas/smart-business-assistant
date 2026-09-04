import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { lazy, StrictMode, Suspense } from 'react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import App from '@/app';
import { AuthGuard, GuestGuard } from '@/components/features/auth/AuthGuard';
import { ProtectedRoute } from '@/components/features/auth/ProtectedRoute';
import { WorkspaceGate } from '@/components/features/business/WorkspaceGate';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout';
import AuthLayout from '@/layouts/AuthLayout';
import AccessDeniedPage from '@/pages/access-denied';
import ForgotPasswordPage from '@/pages/forgot-password';
import LoginPage from '@/pages/login';
import OnboardingPage from '@/pages/onboarding';
import RegisterPage from '@/pages/register';
import ResetPasswordPage from '@/pages/reset-password';
import VerifyEmailPage from '@/pages/verify-email';
import { AuthorizationProvider } from '@/providers/AuthorizationProvider';
import '@/styles/globals.css';

function PageLoader() {
    return (
        <div className="flex min-h-[60vh] items-center justify-center">
            <Spinner className="h-6 w-6" />
        </div>
    );
}

function lazyPage(loader: () => Promise<{ default: ComponentType }>) {
    const LazyComponent = lazy(loader);

    return function LazyPage() {
        return (
            <Suspense fallback={<PageLoader />}>
                <LazyComponent />
            </Suspense>
        );
    };
}

const AIHealthPage = lazyPage(() => import('@/pages/ai/health'));
const PromptEditorPage = lazyPage(() => import('@/pages/ai/prompts'));
const AISettingsPage = lazyPage(() => import('@/pages/ai/settings'));
const AIUsagePage = lazyPage(() => import('@/pages/ai/usage'));
const AnalyticsAgentsPage = lazyPage(() => import('@/pages/analytics/agents'));
const AnalyticsAIPage = lazyPage(() => import('@/pages/analytics/ai'));
const AnalyticsCampaignsPage = lazyPage(() => import('@/pages/analytics/campaigns'));
const AnalyticsConversationsPage = lazyPage(() => import('@/pages/analytics/conversations'));
const AnalyticsCustomersPage = lazyPage(() => import('@/pages/analytics/customers'));
const AnalyticsOverviewPage = lazyPage(() => import('@/pages/analytics/index'));
const AnalyticsKnowledgePage = lazyPage(() => import('@/pages/analytics/knowledge'));
const AnalyticsLayout = lazyPage(() => import('@/pages/analytics/layout'));
const AnalyticsWorkflowsPage = lazyPage(() => import('@/pages/analytics/workflows'));
const BroadcastCampaignDetailPage = lazyPage(() => import('@/pages/broadcast/campaigns/[id]'));
const BroadcastCampaignCreatePage = lazyPage(() => import('@/pages/broadcast/campaigns/create'));
const BroadcastCampaignsPage = lazyPage(() => import('@/pages/broadcast/campaigns/index'));
const BroadcastTemplatesPage = lazyPage(() => import('@/pages/broadcast/templates/index'));
const BusinessSettingsPage = lazyPage(() => import('@/pages/business-settings'));
const CustomerListPage = lazyPage(() => import('@/pages/customers'));
const CustomerCreatePage = lazyPage(() => import('@/pages/customers/create'));
const CustomerDetailPage = lazyPage(() => import('@/pages/customers/customer-detail'));
const CustomerEditPage = lazyPage(() => import('@/pages/customers/customer-edit'));
const DashboardPage = lazyPage(() => import('@/pages/dashboard'));
const InboxPage = lazyPage(() => import('@/pages/inbox'));
const ProfilePage = lazyPage(() => import('@/pages/profile'));
const RAGChunksPage = lazyPage(() => import('@/pages/rag/chunks'));
const RAGContextPage = lazyPage(() => import('@/pages/rag/context'));
const RAGDiagnosticsPage = lazyPage(() => import('@/pages/rag/diagnostics'));
const RAGSearchPage = lazyPage(() => import('@/pages/rag/search'));
const FaqLibraryPage = lazyPage(() => import('@/pages/knowledge-base/faqs'));
const ConnectWhatsAppPage = lazyPage(() => import('@/pages/whatsapp/connect'));
const DiagnosticsPage = lazyPage(() => import('@/pages/whatsapp/diagnostics'));
const WhatsAppSettingsPage = lazyPage(() => import('@/pages/whatsapp/index'));
const WorkflowDetail = lazyPage(() => import('@/pages/workflows/[id]'));
const WorkflowBuilder = lazyPage(() => import('@/pages/workflows/create'));
const WorkflowDashboard = lazyPage(() => import('@/pages/workflows/index'));

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1,
            refetchOnWindowFocus: false,
            staleTime: 5 * 60 * 1000,
        },
    },
});

let root: ReturnType<typeof createRoot> | null = null;

function renderApp() {
    const container = document.getElementById('root')!;

    if (!root) {
        root = createRoot(container);
    }

    root.render(
        <StrictMode>
            <QueryClientProvider client={queryClient}>
                <BrowserRouter>
                    <AuthorizationProvider>
                        <ErrorBoundary>
                            <App>
                                <Routes>
                                    <Route element={<AuthLayout />}>
                                        <Route
                                            path="/login"
                                            element={
                                                <GuestGuard>
                                                    <LoginPage />
                                                </GuestGuard>
                                            }
                                        />
                                        <Route
                                            path="/register"
                                            element={
                                                <GuestGuard>
                                                    <RegisterPage />
                                                </GuestGuard>
                                            }
                                        />
                                        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
                                        <Route path="/reset-password" element={<ResetPasswordPage />} />
                                        <Route path="/verify-email" element={<VerifyEmailPage />} />
                                    </Route>
                                    <Route
                                        element={
                                            <AuthGuard>
                                                <WorkspaceGate>
                                                    <AppLayout />
                                                </WorkspaceGate>
                                            </AuthGuard>
                                        }
                                    >
                                        <Route path="/" element={<DashboardPage />} />
                                        <Route path="/profile" element={<ProfilePage />} />
                                        <Route path="/inbox" element={<InboxPage />} />
                                        <Route path="/inbox/:id" element={<InboxPage />} />
                                        <Route
                                            path="/business/settings"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['settings.view']}
                                                    roles={['business-owner', 'business-admin']}
                                                    requireAll={false}
                                                >
                                                    <BusinessSettingsPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/customers"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['customers.view']}
                                                    requireAll={false}
                                                >
                                                    <CustomerListPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/customers/create"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['customers.create']}
                                                    requireAll={false}
                                                >
                                                    <CustomerCreatePage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/customers/:uuid"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['customers.view']}
                                                    requireAll={false}
                                                >
                                                    <CustomerDetailPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/customers/:uuid/edit"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['customers.update', 'customers.view']}
                                                    requireAll={true}
                                                >
                                                    <CustomerEditPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route path="/rag/search" element={<RAGSearchPage />} />
                                        <Route path="/rag/context" element={<RAGContextPage />} />
                                        <Route path="/rag/chunks" element={<RAGChunksPage />} />
                                        <Route path="/rag/diagnostics" element={<RAGDiagnosticsPage />} />
                                        <Route
                                            path="/knowledge/faqs"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['knowledge.view']}
                                                    requireAll={false}
                                                >
                                                    <FaqLibraryPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/workflows"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['workflows.view']}
                                                    requireAll={false}
                                                >
                                                    <WorkflowDashboard />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/workflows/create"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['workflows.create']}
                                                    requireAll={false}
                                                >
                                                    <WorkflowBuilder />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/workflows/:id"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['workflows.view']}
                                                    requireAll={false}
                                                >
                                                    <WorkflowDetail />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/broadcast/campaigns"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['broadcasts.view']}
                                                    requireAll={false}
                                                >
                                                    <BroadcastCampaignsPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/broadcast/campaigns/create"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['broadcasts.create']}
                                                    requireAll={false}
                                                >
                                                    <BroadcastCampaignCreatePage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/broadcast/campaigns/:id"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['broadcasts.view']}
                                                    requireAll={false}
                                                >
                                                    <BroadcastCampaignDetailPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/broadcast/templates"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['whatsapp.templates.manage']}
                                                    requireAll={false}
                                                >
                                                    <BroadcastTemplatesPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/whatsapp"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['whatsapp.view']}
                                                    requireAll={false}
                                                >
                                                    <WhatsAppSettingsPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/whatsapp/connect"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['whatsapp.phone.manage']}
                                                    requireAll={false}
                                                >
                                                    <ConnectWhatsAppPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/whatsapp/diagnostics"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['whatsapp.view']}
                                                    requireAll={false}
                                                >
                                                    <DiagnosticsPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/ai/settings"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['ai.configure']}
                                                    requireAll={false}
                                                >
                                                    <AISettingsPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/ai/prompts"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['ai.manage_prompts']}
                                                    requireAll={false}
                                                >
                                                    <PromptEditorPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/ai/health"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['ai.view_usage']}
                                                    requireAll={false}
                                                >
                                                    <AIHealthPage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/ai/usage"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['ai.view_usage']}
                                                    requireAll={false}
                                                >
                                                    <AIUsagePage />
                                                </ProtectedRoute>
                                            }
                                        />
                                        <Route
                                            path="/analytics"
                                            element={
                                                <ProtectedRoute
                                                    permissions={['analytics.view']}
                                                    requireAll={false}
                                                >
                                                    <AnalyticsLayout />
                                                </ProtectedRoute>
                                            }
                                        >
                                            <Route index element={<AnalyticsOverviewPage />} />
                                            <Route
                                                path="conversations"
                                                element={<AnalyticsConversationsPage />}
                                            />
                                            <Route path="customers" element={<AnalyticsCustomersPage />} />
                                            <Route path="ai" element={<AnalyticsAIPage />} />
                                            <Route path="agents" element={<AnalyticsAgentsPage />} />
                                            <Route path="campaigns" element={<AnalyticsCampaignsPage />} />
                                            <Route path="workflows" element={<AnalyticsWorkflowsPage />} />
                                            <Route path="knowledge" element={<AnalyticsKnowledgePage />} />
                                        </Route>
                                    </Route>
                                    <Route
                                        path="/onboarding"
                                        element={
                                            <AuthGuard>
                                                <OnboardingPage />
                                            </AuthGuard>
                                        }
                                    />
                                    <Route path="/access-denied" element={<AccessDeniedPage />} />
                                </Routes>
                            </App>
                        </ErrorBoundary>
                    </AuthorizationProvider>
                </BrowserRouter>
            </QueryClientProvider>
        </StrictMode>,
    );
}

renderApp();

if (import.meta.hot) {
    import.meta.hot.accept();
}
