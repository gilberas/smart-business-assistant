import type { Business } from '@/types/business';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    phone: string | null;
    timezone: string;
    locale: string;
    status: string;
    email_verified_at: string | null;
    last_login_at: string | null;
    preferences: Record<string, unknown> | null;
    businesses?: Business[];
    current_business?: Business | null;
    created_at: string;
    updated_at: string;
};

export type AuthResponse = {
    user: User;
    token: string;
    current_business?: Business | null;
};

export type LoginPayload = {
    email: string;
    password: string;
    remember?: boolean;
};

export type RegisterPayload = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type ForgotPasswordPayload = {
    email: string;
};

export type ResetPasswordPayload = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type UpdateProfilePayload = {
    name?: string;
    phone?: string;
    timezone?: string;
    locale?: string;
    avatar?: File;
    preferences?: Record<string, unknown>;
};

export type ChangePasswordPayload = {
    current_password: string;
    new_password: string;
    new_password_confirmation: string;
};
