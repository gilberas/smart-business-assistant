import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUser } from '@/hooks/useAuth';
import { useAuthStore } from '@/stores/auth-store';

interface AuthGuardProps {
    children: ReactNode;
}

export function AuthGuard({ children }: AuthGuardProps) {
    const token = useAuthStore((state) => state.token);
    const clearAuth = useAuthStore((state) => state.clearAuth);
    const { isError } = useUser();
    const navigate = useNavigate();

    useEffect(() => {
        if (isError) {
            clearAuth();
            navigate('/login', { replace: true });
        }
    }, [isError, clearAuth, navigate]);

    useEffect(() => {
        if (!token) {
            navigate('/login', { replace: true });
        }
    }, [token, navigate]);

    if (!token) {
        return null;
    }

    return <>{children}</>;
}

export function GuestGuard({ children }: { children: ReactNode }) {
    const { isAuthenticated } = useAuthStore();
    const navigate = useNavigate();

    useEffect(() => {
        if (isAuthenticated) {
            navigate('/', { replace: true });
        }
    }, [isAuthenticated, navigate]);

    if (isAuthenticated) {
        return null;
    }

    return <>{children}</>;
}
