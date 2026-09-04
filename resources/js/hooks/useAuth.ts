import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from '@/lib/api/auth';
import { useAuthStore } from '@/stores/auth-store';
import { useAuthorizationStore } from '@/stores/authorization-store';
import { useBusinessStore } from '@/stores/business-store';
import type {
    ChangePasswordPayload,
    ForgotPasswordPayload,
    LoginPayload,
    RegisterPayload,
    ResetPasswordPayload,
    UpdateProfilePayload,
} from '@/types/auth';

export function useLogin() {
    const setAuth = useAuthStore((state) => state.setAuth);
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: LoginPayload) => authApi.login(payload),
        onSuccess: (data) => {
            setAuth(data.user, data.token);
            queryClient.invalidateQueries({ queryKey: ['auth-user'] });
            queryClient.invalidateQueries({ queryKey: ['authorization', 'me'] });
            queryClient.invalidateQueries({ queryKey: ['businesses'] });
            queryClient.invalidateQueries({ queryKey: ['current-business'] });
        },
    });
}

export function useRegister() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: RegisterPayload) => authApi.register(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['auth-user'] });
        },
    });
}

export function useLogout() {
    const clearAuth = useAuthStore((state) => state.clearAuth);
    const clearAuthorization = useAuthorizationStore((state) => state.clearAuthorization);
    const clearBusiness = useBusinessStore((state) => state.clearBusiness);
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => authApi.logout(),
        onSettled: () => {
            clearAuth();
            clearAuthorization();
            clearBusiness();
            queryClient.clear();
        },
    });
}

export function useUser() {
    const token = useAuthStore((state) => state.token);
    const setUser = useAuthStore((state) => state.setUser);
    const clearAuth = useAuthStore((state) => state.clearAuth);

    return useQuery({
        queryKey: ['auth-user'],
        queryFn: async () => {
            try {
                const user = await authApi.getUser();
                setUser(user);

                return user;
            } catch {
                clearAuth();

                throw new Error('Not authenticated');
            }
        },
        enabled: !!token,
        retry: false,
        staleTime: 5 * 60 * 1000,
    });
}

export function useForgotPassword() {
    return useMutation({
        mutationFn: (payload: ForgotPasswordPayload) => authApi.forgotPassword(payload),
    });
}

export function useResetPassword() {
    return useMutation({
        mutationFn: (payload: ResetPasswordPayload) => authApi.resetPassword(payload),
    });
}

export function useUpdateProfile() {
    const setUser = useAuthStore((state) => state.setUser);

    return useMutation({
        mutationFn: (payload: UpdateProfilePayload) => authApi.updateProfile(payload),
        onSuccess: (user) => {
            setUser(user);
        },
    });
}

export function useChangePassword() {
    return useMutation({
        mutationFn: (payload: ChangePasswordPayload) => authApi.changePassword(payload),
    });
}

export function useConfirmPassword() {
    return useMutation({
        mutationFn: (password: string) => authApi.confirmPassword(password),
    });
}

export function useDeleteAccount() {
    const clearAuth = useAuthStore((state) => state.clearAuth);

    return useMutation({
        mutationFn: () => authApi.deleteAccount(),
        onSuccess: () => {
            clearAuth();
        },
    });
}
