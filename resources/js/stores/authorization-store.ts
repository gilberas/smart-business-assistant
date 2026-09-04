import { create } from 'zustand';
import type { AuthorizationState, Role } from '@/types/authorization';

interface AuthorizationStoreState {
    permissions: string[];
    role: Role | null;
    isSuperAdmin: boolean;
    isLoading: boolean;
    setAuthorization: (data: AuthorizationState) => void;
    setLoading: (loading: boolean) => void;
    clearAuthorization: () => void;
}

export const useAuthorizationStore = create<AuthorizationStoreState>()((set) => ({
    permissions: [],
    role: null,
    isSuperAdmin: false,
    isLoading: true,

    setAuthorization: (data: AuthorizationState) =>
        set({
            permissions: data.permissions,
            role: data.role as Role | null,
            isSuperAdmin: data.isSuperAdmin,
            isLoading: false,
        }),

    setLoading: (isLoading: boolean) =>
        set({ isLoading }),

    clearAuthorization: () =>
        set({
            permissions: [],
            role: null,
            isSuperAdmin: false,
            isLoading: false,
        }),
}));
