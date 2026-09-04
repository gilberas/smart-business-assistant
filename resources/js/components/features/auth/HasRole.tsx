import type { ReactNode } from 'react';
import { useAuthorization } from '@/hooks/useAuthorization';
import type { Role } from '@/types/authorization';

interface HasRoleProps {
    roles: (Role | string)[];
    fallback?: ReactNode;
    children: ReactNode;
    mode?: 'exact' | 'orAbove';
}

export function HasRole({ roles, fallback = null, children, mode = 'exact' }: HasRoleProps) {
    const { hasRole, hasRoleOrAbove } = useAuthorization();

    const authorized = mode === 'orAbove' ? hasRoleOrAbove(roles[0]) : hasRole(...roles);

    if (authorized) {
        return <>{children}</>;
    }

    return <>{fallback}</>;
}
