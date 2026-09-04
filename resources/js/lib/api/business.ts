import apiClient from '@/lib/api/client';
import type {
    Business,
    BusinessSettings,
    CreateBusinessPayload,
    Member,
    UpdateBusinessPayload,
    UpdateBusinessSettingsPayload,
} from '@/types/business';

export const businessApi = {
    async list(): Promise<{ businesses: Business[]; current_business_id: number | null }> {
        const { data } = await apiClient.get('/businesses');

        return data;
    },

    async create(payload: CreateBusinessPayload): Promise<Business> {
        const { data } = await apiClient.post('/businesses', payload);

        return data.business;
    },

    async get(id: number): Promise<Business> {
        const { data } = await apiClient.get(`/businesses/${id}`);

        return data;
    },

    async update(id: number, payload: UpdateBusinessPayload): Promise<Business> {
        const { data } = await apiClient.put(`/businesses/${id}`, payload);

        return data.business;
    },

    async delete(id: number): Promise<void> {
        await apiClient.delete(`/businesses/${id}`);
    },

    async getCurrent(): Promise<Business> {
        const { data } = await apiClient.get('/business/current');

        return data;
    },

    async switch(businessId: number): Promise<{ business: Business; member_role: string }> {
        const { data } = await apiClient.post('/business/switch', { business_id: businessId });

        return data;
    },

    async getMembers(businessId: number): Promise<Member[]> {
        const { data } = await apiClient.get(`/businesses/${businessId}/members`);

        return data.members;
    },

    async addMember(businessId: number, email: string, role: string): Promise<void> {
        await apiClient.post(`/businesses/${businessId}/members`, { email, role });
    },

    async updateMemberRole(businessId: number, memberId: number, role: string): Promise<void> {
        await apiClient.put(`/businesses/${businessId}/members/${memberId}`, { role });
    },

    async removeMember(businessId: number, memberId: number): Promise<void> {
        await apiClient.delete(`/businesses/${businessId}/members/${memberId}`);
    },

    async getSettings(businessId: number): Promise<BusinessSettings> {
        const { data } = await apiClient.get(`/businesses/${businessId}/settings`);

        return data;
    },

    async updateSettings(
        businessId: number,
        payload: UpdateBusinessSettingsPayload,
    ): Promise<BusinessSettings> {
        const { data } = await apiClient.put(`/businesses/${businessId}/settings`, payload);

        return data.settings;
    },

    async updateOnboarding(businessId: number, step: string): Promise<Business> {
        const { data } = await apiClient.post(`/businesses/${businessId}/onboarding`, { step });

        return data.business;
    },

    async completeOnboarding(businessId: number): Promise<Business> {
        const { data } = await apiClient.post(`/businesses/${businessId}/onboarding/complete`);

        return data.business;
    },
};
