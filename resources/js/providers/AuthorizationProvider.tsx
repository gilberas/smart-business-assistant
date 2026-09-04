import type { ReactNode } from 'react';
import { useAuthorization } from '@/hooks/useAuthorization';

interface AuthorizationProviderProps {
    children: ReactNode;
}

export function AuthorizationProvider({ children }: AuthorizationProviderProps) {
    useAuthorization();

    return <>{children}</>;
}
