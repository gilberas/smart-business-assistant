import { useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import { authorizationApi } from '@/lib/api/authorization';
import { useAuthStore } from '@/stores/auth-store';
import { useAuthorizationStore } from '@/stores/authorization-store';
import { useBusinessStore } from '@/stores/business-store';
import type { Permission, Role } from '@/types/authorization';
import { ROLE_HIERARCHY } from '@/types/authorization';

export function useAuthorization() {
    const { permissions, role, isSuperAdmin, isLoading, setAuthorization, clearAuthorization } =
        useAuthorizationStore();
    const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

    const query = useQuery({
        queryKey: ['authorization', 'me'],
        queryFn: async () => {
            const data = await authorizationApi.getMyAuthorization();
            setAuthorization(data);

            return data;
        },
        enabled: isAuthenticated,
        retry: false,
        staleTime: 5 * 60 * 1000,
    });

    useEffect(() => {
        if (query.isError) {
            clearAuthorization();
        }
    }, [query.isError, clearAuthorization]);

    const can = (permission: Permission | string): boolean => {
        if (isSuperAdmin) {
return true;
}

        return permissions.includes(permission);
    };

    const canAny = (...perms: (Permission | string)[]): boolean => {
        if (isSuperAdmin) {
return true;
}

        return perms.some((p) => permissions.includes(p));
    };

    const canAll = (...perms: (Permission | string)[]): boolean => {
        if (isSuperAdmin) {
return true;
}

        return perms.every((p) => permissions.includes(p));
    };

    const hasRole = (...roles: (Role | string)[]): boolean => {
        if (!role) {
return false;
}

        if (isSuperAdmin) {
return true;
}

        return roles.includes(role);
    };

    const hasRoleOrAbove = (minimumRole: Role | string): boolean => {
        if (!role) {
return false;
}

        if (isSuperAdmin) {
return true;
}

        const currentRank = ROLE_HIERARCHY[role as Role] ?? 0;
        const minimumRank = ROLE_HIERARCHY[minimumRole as Role] ?? 0;

        return currentRank >= minimumRank;
    };

    const cannot = (permission: Permission | string): boolean => {
        return !can(permission);
    };

    return {
        permissions,
        role,
        isSuperAdmin,
        isLoading: isLoading || query.isLoading,
        can,
        canAny,
        canAll,
        cannot,
        hasRole,
        hasRoleOrAbove,
        refetch: query.refetch,
        clearAuthorization,
    };
}

export function useBusinessPermissions() {
    const businesses = useBusinessStore((state) => state.businesses);
    const currentBusiness = useBusinessStore((state) => state.currentBusiness);

    const currentRole = currentBusiness
        ? (currentBusiness as { member_role?: string | null }).member_role ?? null
        : null;

    return {
        currentBusiness,
        businesses,
        currentRole,
        isOwner: currentBusiness
            ? (currentBusiness as { member_role?: string | null }).member_role === 'owner'
            : false,
    };
}
