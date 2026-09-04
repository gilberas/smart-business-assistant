import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { useBusinesses } from '@/hooks/useBusiness';
import { useAuthorizationStore } from '@/stores/authorization-store';
import { useBusinessStore } from '@/stores/business-store';

interface WorkspaceGateProps {
    children: ReactNode;
}

export function WorkspaceGate({ children }: WorkspaceGateProps) {
    const { isLoading, isError, refetch } = useBusinesses();
    const businesses = useBusinessStore((state) => state.businesses);
    const isSuperAdmin = useAuthorizationStore((state) => state.isSuperAdmin);

    if (isError) {
        return (
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 px-4 text-center">
                <div className="space-y-2">
                    <h2 className="text-xl font-semibold">We couldn't load your workspaces</h2>
                    <p className="max-w-md text-sm text-muted-foreground">
                        There was a problem fetching your business workspaces. Please try again.
                    </p>
                </div>
                <Button onClick={() => refetch()}>Try again</Button>
            </div>
        );
    }

    // Super admins are platform-wide and are not required to own a business,
    // so they should never be bounced to the onboarding wizard.
    if (!isLoading && businesses.length === 0 && !isSuperAdmin) {
        return <Navigate to="/onboarding" replace />;
    }

    return <>{children}</>;
}
