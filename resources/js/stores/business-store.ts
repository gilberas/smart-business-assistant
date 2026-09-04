import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Business } from '@/types/business';

interface BusinessState {
    businesses: Business[];
    currentBusiness: Business | null;
    currentBusinessId: number | null;
    isLoading: boolean;
    setBusinesses: (businesses: Business[], currentId: number | null) => void;
    setCurrentBusiness: (business: Business) => void;
    setLoading: (loading: boolean) => void;
    clearBusiness: () => void;
}

export const useBusinessStore = create<BusinessState>()(
    persist(
        (set) => ({
            businesses: [],
            currentBusiness: null,
            currentBusinessId: null,
            isLoading: false,

            setBusinesses: (businesses: Business[], currentId: number | null) =>
                set({
                    businesses,
                    currentBusinessId: currentId,
                    currentBusiness: businesses.find((b) => b.id === currentId) || businesses[0] || null,
                }),

            setCurrentBusiness: (business: Business) =>
                set({
                    currentBusiness: business,
                    currentBusinessId: business.id,
                }),

            setLoading: (isLoading: boolean) =>
                set({ isLoading }),

            clearBusiness: () =>
                set({
                    businesses: [],
                    currentBusiness: null,
                    currentBusinessId: null,
                }),
        }),
        {
            name: 'business-storage',
            partialize: (state) => ({
                currentBusinessId: state.currentBusinessId,
            }),
        },
    ),
);
