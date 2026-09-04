import { create } from 'zustand';
import type { Customer, CustomerFilters, CustomerStats } from '@/types/customer';

interface CustomerState {
    customers: Customer[];
    selectedCustomer: Customer | null;
    filters: CustomerFilters;
    stats: CustomerStats | null;
    isLoading: boolean;

    setCustomers: (customers: Customer[]) => void;
    setSelectedCustomer: (customer: Customer | null) => void;
    setFilters: (filters: Partial<CustomerFilters>) => void;
    resetFilters: () => void;
    setStats: (stats: CustomerStats | null) => void;
    setLoading: (loading: boolean) => void;
    removeCustomer: (uuid: string) => void;
}

const initialFilters: CustomerFilters = {};

export const useCustomerStore = create<CustomerState>()((set) => ({
    customers: [],
    selectedCustomer: null,
    filters: initialFilters,
    stats: null,
    isLoading: false,

    setCustomers: (customers) => set({ customers }),

    setSelectedCustomer: (customer) => set({ selectedCustomer: customer }),

    setFilters: (filters) =>
        set((state) => ({ filters: { ...state.filters, ...filters } })),

    resetFilters: () => set({ filters: initialFilters }),

    setStats: (stats) => set({ stats }),

    setLoading: (isLoading) => set({ isLoading }),

    removeCustomer: (uuid) =>
        set((state) => ({
            customers: state.customers.filter((c) => c.uuid !== uuid),
            selectedCustomer:
                state.selectedCustomer?.uuid === uuid ? null : state.selectedCustomer,
        })),
}));
