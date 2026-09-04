import apiClient from '@/lib/api/client';
import type { AuthorizationResponse } from '@/types/authorization';

export const authorizationApi = {
    getMyAuthorization: async (): Promise<AuthorizationResponse> => {
        const response = await apiClient.get('/authorization/me');
        const data = response.data;
        return {
            ...data,
            isSuperAdmin: data.is_super_admin ?? data.isSuperAdmin ?? false,
        };
    },

    getPermissions: async () => {
        const response = await apiClient.get('/authorization/permissions');
        return response.data;
    },

    getRoles: async () => {
        const response = await apiClient.get('/authorization/roles');
        return response.data;
    },

    getRegistry: async () => {
        const response = await apiClient.get('/authorization/registry');
        return response.data;
    },

    getPermissionGroups: async () => {
        const response = await apiClient.get('/authorization/permissions/groups');
        return response.data;
    },

    getRoleHierarchy: async () => {
        const response = await apiClient.get('/authorization/roles/hierarchy');
        return response.data;
    },

    syncPermissions: async () => {
        const response = await apiClient.post('/authorization/permissions/sync');
        return response.data;
    },
};
