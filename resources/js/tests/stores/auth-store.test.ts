import { describe, it, expect, beforeEach } from 'vitest';
import { useAuthStore } from '@/stores/auth-store';

describe('Auth Store', () => {
    beforeEach(() => {
        useAuthStore.setState({
            user: null,
            token: null,
            isAuthenticated: false,
        });
    });

    it('should start with default values', () => {
        const state = useAuthStore.getState();
        expect(state.user).toBeNull();
        expect(state.token).toBeNull();
        expect(state.isAuthenticated).toBe(false);
    });

    it('should set auth state', () => {
        const mockUser = {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            avatar: null,
            phone: null,
            timezone: 'UTC',
            locale: 'en',
            status: 'active',
            email_verified_at: null,
            last_login_at: null,
            preferences: null,
            created_at: '2026-01-01T00:00:00.000000Z',
            updated_at: '2026-01-01T00:00:00.000000Z',
        };

        useAuthStore.getState().setAuth(mockUser, 'test-token');

        const state = useAuthStore.getState();
        expect(state.user).toEqual(mockUser);
        expect(state.token).toBe('test-token');
        expect(state.isAuthenticated).toBe(true);
    });

    it('should clear auth state', () => {
        useAuthStore.getState().setAuth(
            { id: 1, name: 'Test', email: 'test@test.com', avatar: null, phone: null, timezone: 'UTC', locale: 'en', status: 'active', email_verified_at: null, last_login_at: null, preferences: null, created_at: '', updated_at: '' },
            'test-token',
        );
        useAuthStore.getState().clearAuth();

        const state = useAuthStore.getState();
        expect(state.user).toBeNull();
        expect(state.token).toBeNull();
        expect(state.isAuthenticated).toBe(false);
    });

    it('should update user', () => {
        useAuthStore.getState().setAuth(
            { id: 1, name: 'Old Name', email: 'test@test.com', avatar: null, phone: null, timezone: 'UTC', locale: 'en', status: 'active', email_verified_at: null, last_login_at: null, preferences: null, created_at: '', updated_at: '' },
            'test-token',
        );

        useAuthStore.getState().setUser(
            { id: 1, name: 'New Name', email: 'test@test.com', avatar: null, phone: null, timezone: 'UTC', locale: 'en', status: 'active', email_verified_at: null, last_login_at: null, preferences: null, created_at: '', updated_at: '' },
        );

        const state = useAuthStore.getState();
        expect(state.user?.name).toBe('New Name');
    });
});
