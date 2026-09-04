import type { ReactNode } from 'react';
import { useAuthorization } from '@/hooks/useAuthorization';
import type { Permission } from '@/types/authorization';

interface CanProps {
    permission: Permission | string;
    fallback?: ReactNode;
    children: ReactNode;
}

export function Can({ permission, fallback = null, children }: CanProps) {
    const { can } = useAuthorization();

    if (can(permission)) {
        return <>{children}</>;
    }

    return <>{fallback}</>;
}

interface CanAnyProps {
    permissions: (Permission | string)[];
    fallback?: ReactNode;
    children: ReactNode;
}

export function CanAny({ permissions, fallback = null, children }: CanAnyProps) {
    const { canAny } = useAuthorization();

    if (canAny(...permissions)) {
        return <>{children}</>;
    }

    return <>{fallback}</>;
}

interface CanAllProps {
    permissions: (Permission | string)[];
    fallback?: ReactNode;
    children: ReactNode;
}

export function CanAll({ permissions, fallback = null, children }: CanAllProps) {
    const { canAll } = useAuthorization();

    if (canAll(...permissions)) {
        return <>{children}</>;
    }

    return <>{fallback}</>;
}
