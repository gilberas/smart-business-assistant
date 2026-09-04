import apiClient from '@/lib/api/client';
import type {
    AuthResponse,
    ChangePasswordPayload,
    ForgotPasswordPayload,
    LoginPayload,
    RegisterPayload,
    ResetPasswordPayload,
    UpdateProfilePayload,
    User,
} from '@/types/auth';

export const authApi = {
    async register(payload: RegisterPayload): Promise<User> {
        const { data } = await apiClient.post('/auth/register', payload);
        return data.user;
    },

    async login(payload: LoginPayload): Promise<AuthResponse> {
        const { data } = await apiClient.post('/auth/login', payload);
        return data;
    },

    async logout(): Promise<void> {
        await apiClient.post('/auth/logout');
    },

    async logoutAllDevices(): Promise<void> {
        await apiClient.post('/auth/logout/all');
    },

    async getUser(): Promise<User> {
        const { data } = await apiClient.get('/auth/user');
        return data;
    },

    async forgotPassword(payload: ForgotPasswordPayload): Promise<string> {
        const { data } = await apiClient.post('/auth/forgot-password', payload);
        return data.message;
    },

    async resetPassword(payload: ResetPasswordPayload): Promise<string> {
        const { data } = await apiClient.post('/auth/reset-password', payload);
        return data.message;
    },

    async confirmPassword(password: string): Promise<string> {
        const { data } = await apiClient.post('/auth/confirm-password', { password });
        return data.message;
    },

    async resendVerificationEmail(): Promise<string> {
        const { data } = await apiClient.post('/auth/email/resend');
        return data.message;
    },

    async updateProfile(payload: UpdateProfilePayload): Promise<User> {
        const formData = new FormData();
        const jsonPayload: Record<string, unknown> = { ...payload };
        delete jsonPayload.avatar;

        formData.append('_method', 'PUT');
        for (const [key, value] of Object.entries(jsonPayload)) {
            if (value !== undefined) {
                formData.append(key, typeof value === 'object' ? JSON.stringify(value) : String(value));
            }
        }
        if (payload.avatar) {
            formData.append('avatar', payload.avatar);
        }

        const { data } = await apiClient.post('/auth/profile', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data;
    },

    async changePassword(payload: ChangePasswordPayload): Promise<string> {
        const { data } = await apiClient.put('/auth/profile/password', payload);
        return data.message;
    },

    async deleteAccount(): Promise<string> {
        const { data } = await apiClient.delete('/auth/profile');
        return data.message;
    },
};
