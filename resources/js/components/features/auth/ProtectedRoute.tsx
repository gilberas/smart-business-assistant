import { Navigate, useLocation } from 'react-router-dom';
import { Spinner } from '@/components/ui/spinner';
import { useAuthorization } from '@/hooks/useAuthorization';
import { useAuthorizationStore } from '@/stores/authorization-store';
import type { Permission, Role } from '@/types/authorization';

interface ProtectedRouteProps {
    children: React.ReactNode;
    permissions?: (Permission | string)[];
    roles?: (Role | string)[];
    requireAll?: boolean;
    fallbackPath?: string;
}

export function ProtectedRoute({
    children,
    permissions,
    roles,
    requireAll = true,
    fallbackPath = '/access-denied',
}: ProtectedRouteProps) {
    const location = useLocation();
    const { isSuperAdmin } = useAuthorizationStore();
    const { isLoading, canAll, canAny, hasRole } = useAuthorization();

    if (isLoading) {
        return (
            <div className="flex min-h-[60vh] items-center justify-center">
                <Spinner className="h-6 w-6" />
            </div>
        );
    }

    if (isSuperAdmin) {
        return <>{children}</>;
    }

    if (permissions && permissions.length > 0) {
        const hasPermission = requireAll ? canAll(...permissions) : canAny(...permissions);

        if (!hasPermission) {
            return <Navigate to={fallbackPath} state={{ from: location }} replace />;
        }
    }

    if (roles && roles.length > 0) {
        const hasRequiredRole = roles.some((role) => hasRole(role));

        if (!hasRequiredRole) {
            return <Navigate to={fallbackPath} state={{ from: location }} replace />;
        }
    }

    return <>{children}</>;
}
