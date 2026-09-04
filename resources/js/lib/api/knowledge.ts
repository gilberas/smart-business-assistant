import apiClient from '@/lib/api/client';
import type { PaginatedResponse, ApiResponse } from '@/types/common';
import type {
    KnowledgeArticle,
    KnowledgeArticleList,
    KnowledgeCategory,
    KnowledgeTag,
    KnowledgeFaq,
    KnowledgeCollection,
    KnowledgeCollectionDetail,
    BusinessInformation,
    KnowledgeMedia,
    KnowledgeArticleStats,
} from '@/types/knowledge';

function buildQuery(filters?: Record<string, unknown>): string {
    if (!filters) return '';
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`${key}[]`, String(v)));
        } else {
            params.append(key, String(value));
        }
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
}

export const knowledgeApi = {
    // Articles
    async listArticles(filters?: Record<string, unknown>): Promise<PaginatedResponse<KnowledgeArticleList>> {
        const { data } = await apiClient.get(`/knowledge/articles${buildQuery(filters)}`);
        return data;
    },

    async getArticle(uuid: string): Promise<KnowledgeArticle> {
        const { data } = await apiClient.get(`/knowledge/articles/${uuid}`);
        return data.data;
    },

    async createArticle(payload: CreateArticlePayload): Promise<KnowledgeArticle> {
        const { data } = await apiClient.post('/knowledge/articles', payload);
        return data.data;
    },

    async updateArticle(uuid: string, payload: UpdateArticlePayload): Promise<KnowledgeArticle> {
        const { data } = await apiClient.put(`/knowledge/articles/${uuid}`, payload);
        return data.data;
    },

    async deleteArticle(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/articles/${uuid}`);
    },

    async publishArticle(uuid: string): Promise<KnowledgeArticle> {
        const { data } = await apiClient.post(`/knowledge/articles/${uuid}/publish`);
        return data.data;
    },

    async archiveArticle(uuid: string): Promise<KnowledgeArticle> {
        const { data } = await apiClient.post(`/knowledge/articles/${uuid}/archive`);
        return data.data;
    },

    async restoreArticle(uuid: string): Promise<KnowledgeArticle> {
        const { data } = await apiClient.post(`/knowledge/articles/${uuid}/restore`);
        return data.data;
    },

    async search(q: string, limit = 10): Promise<KnowledgeArticleList[]> {
        const { data } = await apiClient.get(`/knowledge/articles/search?q=${encodeURIComponent(q)}&limit=${limit}`);
        return data.data;
    },

    async stats(): Promise<KnowledgeArticleStats> {
        const { data } = await apiClient.get('/knowledge/articles/stats');
        return data;
    },

    async trackView(uuid: string): Promise<void> {
        await apiClient.post(`/knowledge/articles/${uuid}/view`);
    },

    async markHelpful(uuid: string): Promise<void> {
        await apiClient.post(`/knowledge/articles/${uuid}/helpful`);
    },

    async markNotHelpful(uuid: string): Promise<void> {
        await apiClient.post(`/knowledge/articles/${uuid}/not-helpful`);
    },

    // Categories
    async listCategories(params?: { is_active?: boolean; parent_id?: number }): Promise<KnowledgeCategory[]> {
        const { data } = await apiClient.get(`/knowledge/categories${buildQuery(params)}`);
        return data.data;
    },

    async tree(): Promise<KnowledgeCategory[]> {
        const { data } = await apiClient.get('/knowledge/categories/tree');
        return data.data;
    },

    async getCategory(uuid: string): Promise<KnowledgeCategory> {
        const { data } = await apiClient.get(`/knowledge/categories/${uuid}`);
        return data.data;
    },

    async createCategory(payload: CreateCategoryPayload): Promise<KnowledgeCategory> {
        const { data } = await apiClient.post('/knowledge/categories', payload);
        return data.data;
    },

    async updateCategory(uuid: string, payload: UpdateCategoryPayload): Promise<KnowledgeCategory> {
        const { data } = await apiClient.put(`/knowledge/categories/${uuid}`, payload);
        return data.data;
    },

    async deleteCategory(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/categories/${uuid}`);
    },

    // Tags
    async listTags(): Promise<KnowledgeTag[]> {
        const { data } = await apiClient.get('/knowledge/tags');
        return data.data;
    },

    async getTag(uuid: string): Promise<KnowledgeTag> {
        const { data } = await apiClient.get(`/knowledge/tags/${uuid}`);
        return data.data;
    },

    async createTag(payload: CreateTagPayload): Promise<KnowledgeTag> {
        const { data } = await apiClient.post('/knowledge/tags', payload);
        return data.data;
    },

    async updateTag(uuid: string, payload: UpdateTagPayload): Promise<KnowledgeTag> {
        const { data } = await apiClient.put(`/knowledge/tags/${uuid}`, payload);
        return data.data;
    },

    async deleteTag(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/tags/${uuid}`);
    },

    // FAQs
    async listFaqs(filters?: Record<string, unknown>): Promise<PaginatedResponse<KnowledgeFaq>> {
        const { data } = await apiClient.get(`/knowledge/faqs${buildQuery(filters)}`);
        return data;
    },

    async getFaq(uuid: string): Promise<KnowledgeFaq> {
        const { data } = await apiClient.get(`/knowledge/faqs/${uuid}`);
        return data.data;
    },

    async createFaq(payload: CreateFaqPayload): Promise<KnowledgeFaq> {
        const { data } = await apiClient.post('/knowledge/faqs', payload);
        return data.data;
    },

    async updateFaq(uuid: string, payload: UpdateFaqPayload): Promise<KnowledgeFaq> {
        const { data } = await apiClient.put(`/knowledge/faqs/${uuid}`, payload);
        return data.data;
    },

    async deleteFaq(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/faqs/${uuid}`);
    },

    // Collections
    async listCollections(): Promise<KnowledgeCollection[]> {
        const { data } = await apiClient.get('/knowledge/collections');
        return data.data;
    },

    async getCollection(uuid: string): Promise<KnowledgeCollectionDetail> {
        const { data } = await apiClient.get(`/knowledge/collections/${uuid}`);
        return data.data;
    },

    async createCollection(payload: CreateCollectionPayload): Promise<KnowledgeCollection> {
        const { data } = await apiClient.post('/knowledge/collections', payload);
        return data.data;
    },

    async updateCollection(uuid: string, payload: UpdateCollectionPayload): Promise<KnowledgeCollection> {
        const { data } = await apiClient.put(`/knowledge/collections/${uuid}`, payload);
        return data.data;
    },

    async deleteCollection(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/collections/${uuid}`);
    },

    async attachArticle(collectionUuid: string, articleId: number, sortOrder = 0): Promise<void> {
        await apiClient.post(`/knowledge/collections/${collectionUuid}/articles`, { article_id: articleId, sort_order: sortOrder });
    },

    async detachArticle(collectionUuid: string, articleId: number): Promise<void> {
        await apiClient.delete(`/knowledge/collections/${collectionUuid}/articles/${articleId}`);
    },

    // Business Information
    async listBusinessInfo(params?: { type?: string; is_public?: boolean }): Promise<BusinessInformation[]> {
        const { data } = await apiClient.get(`/knowledge/business-info${buildQuery(params)}`);
        return data.data;
    },

    async groupedBusinessInfo(): Promise<Record<string, BusinessInformation[]>> {
        const { data } = await apiClient.get('/knowledge/business-info/grouped');
        return data.data;
    },

    async getBusinessInfo(uuid: string): Promise<BusinessInformation> {
        const { data } = await apiClient.get(`/knowledge/business-info/${uuid}`);
        return data.data;
    },

    async createBusinessInfo(payload: CreateBusinessInfoPayload): Promise<BusinessInformation> {
        const { data } = await apiClient.post('/knowledge/business-info', payload);
        return data.data;
    },

    async updateBusinessInfo(uuid: string, payload: UpdateBusinessInfoPayload): Promise<BusinessInformation> {
        const { data } = await apiClient.put(`/knowledge/business-info/${uuid}`, payload);
        return data.data;
    },

    async deleteBusinessInfo(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/business-info/${uuid}`);
    },

    // Media
    async listMedia(articleUuid: string): Promise<KnowledgeMedia[]> {
        const { data } = await apiClient.get(`/knowledge/articles/${articleUuid}/media`);
        return data.data;
    },

    async uploadMedia(articleUuid: string, payload: UploadMediaPayload): Promise<KnowledgeMedia> {
        const formData = new FormData();
        formData.append('file', payload.file);
        if (payload.media_type) formData.append('media_type', payload.media_type);

        const { data } = await apiClient.post(`/knowledge/articles/${articleUuid}/media`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data.data;
    },

    async deleteMedia(uuid: string): Promise<void> {
        await apiClient.delete(`/knowledge/media/${uuid}`);
    },
};

// Payload types
export type CreateArticlePayload = {
    title: string;
    slug?: string;
    excerpt?: string;
    content?: string;
    category_id?: number;
    article_type?: string;
    status?: string;
    visibility?: string;
    featured_image?: string;
    meta_title?: string;
    meta_description?: string;
    meta_keywords?: string;
    is_featured?: boolean;
    published_at?: string;
    tag_ids?: number[];
    tags?: string[];
};

export type UpdateArticlePayload = Partial<CreateArticlePayload>;

export type CreateCategoryPayload = {
    name: string;
    slug?: string;
    parent_id?: number;
    description?: string;
    icon?: string;
    color?: string;
    sort_order?: number;
    is_active?: boolean;
};

export type UpdateCategoryPayload = Partial<CreateCategoryPayload>;

export type CreateTagPayload = {
    name: string;
    color?: string;
};

export type UpdateTagPayload = Partial<CreateTagPayload>;

export type CreateFaqPayload = {
    question: string;
    answer?: string;
    category_id?: number;
    article_id?: number;
    sort_order?: number;
    is_published?: boolean;
};

export type UpdateFaqPayload = Partial<CreateFaqPayload>;

export type CreateCollectionPayload = {
    name: string;
    slug?: string;
    description?: string;
    cover_image?: string;
    is_featured?: boolean;
    sort_order?: number;
};

export type UpdateCollectionPayload = Partial<CreateCollectionPayload>;

export type CreateBusinessInfoPayload = {
    type: string;
    key: string;
    value?: string;
    metadata?: Record<string, unknown>;
    is_public?: boolean;
    sort_order?: number;
};

export type UpdateBusinessInfoPayload = Partial<CreateBusinessInfoPayload>;

export type UploadMediaPayload = {
    file: File;
    media_type?: string;
};