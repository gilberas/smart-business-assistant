import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { knowledgeApi } from '@/lib/api/knowledge';
import { useKnowledgeStore } from '@/stores/knowledge-store';
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
    KnowledgeArticleFilters,
    KnowledgeArticleStats,
    CreateArticlePayload,
    UpdateArticlePayload,
    CreateCategoryPayload,
    UpdateCategoryPayload,
    CreateTagPayload,
    UpdateTagPayload,
    CreateFaqPayload,
    UpdateFaqPayload,
    CreateCollectionPayload,
    UpdateCollectionPayload,
    CreateBusinessInfoPayload,
    UpdateBusinessInfoPayload,
    UploadMediaPayload,
} from '@/types/knowledge';

// Articles
export function useKnowledgeArticles(filters?: KnowledgeArticleFilters) {
    const setArticles = useKnowledgeStore((s) => s.setArticles);
    const setLoading = useKnowledgeStore((s) => s.setLoading);

    return useQuery({
        queryKey: ['knowledge-articles', filters],
        queryFn: async () => {
            setLoading(true);
            try {
                const data = await knowledgeApi.listArticles(filters);
                setArticles(data.data);
                return data;
            } finally {
                setLoading(false);
            }
        },
    });
}

export function useKnowledgeArticle(uuid: string | undefined) {
    const setSelectedArticle = useKnowledgeStore((s) => s.setSelectedArticle);

    return useQuery({
        queryKey: ['knowledge-article', uuid],
        queryFn: async () => {
            const article = await knowledgeApi.getArticle(uuid!);
            setSelectedArticle(article);
            return article;
        },
        enabled: !!uuid,
    });
}

export function useCreateKnowledgeArticle() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateArticlePayload) => knowledgeApi.createArticle(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article-stats'] });
        },
    });
}

export function useUpdateKnowledgeArticle(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateArticlePayload) => knowledgeApi.updateArticle(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article', uuid] });
        },
    });
}

export function useDeleteKnowledgeArticle() {
    const queryClient = useQueryClient();
    const removeArticle = useKnowledgeStore((s) => s.removeArticle);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteArticle(uuid),
        onSuccess: (_data, uuid) => {
            removeArticle(uuid);
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article-stats'] });
        },
    });
}

export function usePublishKnowledgeArticle() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.publishArticle(uuid),
        onSuccess: (_data, uuid) => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article', uuid] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article-stats'] });
        },
    });
}

export function useArchiveKnowledgeArticle() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.archiveArticle(uuid),
        onSuccess: (_data, uuid) => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article', uuid] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article-stats'] });
        },
    });
}

export function useRestoreKnowledgeArticle() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.restoreArticle(uuid),
        onSuccess: (_data, uuid) => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-articles'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article', uuid] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-article-stats'] });
        },
    });
}

export function useSearchKnowledgeArticles(q: string) {
    return useQuery({
        queryKey: ['knowledge-articles-search', q],
        queryFn: () => knowledgeApi.search(q),
        enabled: q.length >= 2,
    });
}

export function useKnowledgeArticleStats() {
    const setStats = useKnowledgeStore((s) => s.setStats);

    return useQuery({
        queryKey: ['knowledge-article-stats'],
        queryFn: async () => {
            const stats = await knowledgeApi.stats();
            setStats(stats);
            return stats;
        },
        staleTime: 2 * 60 * 1000,
    });
}

export function useTrackArticleView() {
    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.trackView(uuid),
    });
}

export function useMarkArticleHelpful() {
    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.markHelpful(uuid),
    });
}

export function useMarkArticleNotHelpful() {
    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.markNotHelpful(uuid),
    });
}

// Categories
export function useKnowledgeCategories(params?: { is_active?: boolean; parent_id?: number }) {
    const setCategories = useKnowledgeStore((s) => s.setCategories);

    return useQuery({
        queryKey: ['knowledge-categories', params],
        queryFn: async () => {
            const categories = await knowledgeApi.listCategories(params);
            setCategories(categories);
            return categories;
        },
    });
}

export function useKnowledgeCategoryTree() {
    const setCategoryTree = useKnowledgeStore((s) => s.setCategories);

    return useQuery({
        queryKey: ['knowledge-category-tree'],
        queryFn: async () => {
            const categories = await knowledgeApi.tree();
            setCategoryTree(categories);
            return categories;
        },
    });
}

export function useCreateKnowledgeCategory() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateCategoryPayload) => knowledgeApi.createCategory(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-categories'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-category-tree'] });
        },
    });
}

export function useUpdateKnowledgeCategory(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateCategoryPayload) => knowledgeApi.updateCategory(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-categories'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-category-tree'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-category', uuid] });
        },
    });
}

export function useDeleteKnowledgeCategory() {
    const queryClient = useQueryClient();
    const removeCategory = useKnowledgeStore((s) => s.removeCategory);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteCategory(uuid),
        onSuccess: (_data, uuid) => {
            removeCategory(uuid);
            queryClient.invalidateQueries({ queryKey: ['knowledge-categories'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-category-tree'] });
        },
    });
}

// Tags
export function useKnowledgeTags() {
    const setTags = useKnowledgeStore((s) => s.setTags);

    return useQuery({
        queryKey: ['knowledge-tags'],
        queryFn: async () => {
            const tags = await knowledgeApi.listTags();
            setTags(tags);
            return tags;
        },
        staleTime: 10 * 60 * 1000,
    });
}

export function useCreateKnowledgeTag() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateTagPayload) => knowledgeApi.createTag(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-tags'] });
        },
    });
}

export function useUpdateKnowledgeTag(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateTagPayload) => knowledgeApi.updateTag(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-tags'] });
        },
    });
}

export function useDeleteKnowledgeTag() {
    const queryClient = useQueryClient();
    const removeTag = useKnowledgeStore((s) => s.removeTag);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteTag(uuid),
        onSuccess: (_data, uuid) => {
            removeTag(uuid);
            queryClient.invalidateQueries({ queryKey: ['knowledge-tags'] });
        },
    });
}

// FAQs
export function useKnowledgeFaqs(filters?: Record<string, unknown>) {
    const setFaqs = useKnowledgeStore((s) => s.setFaqs);

    return useQuery({
        queryKey: ['knowledge-faqs', filters],
        queryFn: async () => {
            const data = await knowledgeApi.listFaqs(filters);
            setFaqs(data.data);
            return data;
        },
    });
}

export function useCreateKnowledgeFaq() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateFaqPayload) => knowledgeApi.createFaq(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-faqs'] });
        },
    });
}

export function useUpdateKnowledgeFaq(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateFaqPayload) => knowledgeApi.updateFaq(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-faqs'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-faq', uuid] });
        },
    });
}

export function useDeleteKnowledgeFaq() {
    const queryClient = useQueryClient();
    const removeFaq = useKnowledgeStore((s) => s.removeFaq);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteFaq(uuid),
        onSuccess: (_data, uuid) => {
            removeFaq(uuid);
            queryClient.invalidateQueries({ queryKey: ['knowledge-faqs'] });
        },
    });
}

// Collections
export function useKnowledgeCollections() {
    const setCollections = useKnowledgeStore((s) => s.setCollections);

    return useQuery({
        queryKey: ['knowledge-collections'],
        queryFn: async () => {
            const collections = await knowledgeApi.listCollections();
            setCollections(collections);
            return collections;
        },
    });
}

export function useCreateKnowledgeCollection() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateCollectionPayload) => knowledgeApi.createCollection(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-collections'] });
        },
    });
}

export function useKnowledgeCollection(uuid: string | undefined) {
    const setSelectedCollection = useKnowledgeStore((s) => s.setSelectedCollection);

    return useQuery({
        queryKey: ['knowledge-collection', uuid],
        queryFn: async () => {
            const collection = await knowledgeApi.getCollection(uuid!);
            setSelectedCollection(collection);
            return collection;
        },
        enabled: !!uuid,
    });
}

export function useUpdateKnowledgeCollection(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateCollectionPayload) => knowledgeApi.updateCollection(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-collections'] });
            queryClient.invalidateQueries({ queryKey: ['knowledge-collection', uuid] });
        },
    });
}

export function useDeleteKnowledgeCollection() {
    const queryClient = useQueryClient();
    const removeCollection = useKnowledgeStore((s) => s.removeCollection);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteCollection(uuid),
        onSuccess: (_data, uuid) => {
            removeCollection(uuid);
            queryClient.invalidateQueries({ queryKey: ['knowledge-collections'] });
        },
    });
}

export function useAttachArticleToCollection(collectionUuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ articleId, sortOrder }: { articleId: number; sortOrder?: number }) =>
            knowledgeApi.attachArticle(collectionUuid!, articleId, sortOrder),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-collection', collectionUuid] });
        },
    });
}

export function useDetachArticleFromCollection(collectionUuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (articleId: number) => knowledgeApi.detachArticle(collectionUuid!, articleId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-collection', collectionUuid] });
        },
    });
}

// Business Information
export function useBusinessInformation(params?: { type?: string; is_public?: boolean }) {
    const setBusinessInfo = useKnowledgeStore((s) => s.setBusinessInfo);

    return useQuery({
        queryKey: ['business-information', params],
        queryFn: async () => {
            const info = await knowledgeApi.listBusinessInfo(params);
            setBusinessInfo(info);
            return info;
        },
    });
}

export function useGroupedBusinessInformation() {
    const setGroupedBusinessInfo = useKnowledgeStore((s) => s.setGroupedBusinessInfo);

    return useQuery({
        queryKey: ['business-information-grouped'],
        queryFn: async () => {
            const grouped = await knowledgeApi.groupedBusinessInfo();
            setGroupedBusinessInfo(grouped);
            return grouped;
        },
    });
}

export function useCreateBusinessInformation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CreateBusinessInfoPayload) => knowledgeApi.createBusinessInfo(payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-information'] });
            queryClient.invalidateQueries({ queryKey: ['business-information-grouped'] });
        },
    });
}

export function useUpdateBusinessInformation(uuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UpdateBusinessInfoPayload) => knowledgeApi.updateBusinessInfo(uuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['business-information'] });
            queryClient.invalidateQueries({ queryKey: ['business-information-grouped'] });
        },
    });
}

export function useDeleteBusinessInformation() {
    const queryClient = useQueryClient();
    const removeBusinessInfo = useKnowledgeStore((s) => s.removeBusinessInfo);

    return useMutation({
        mutationFn: (uuid: string) => knowledgeApi.deleteBusinessInfo(uuid),
        onSuccess: (_data, uuid) => {
            removeBusinessInfo(uuid);
            queryClient.invalidateQueries({ queryKey: ['business-information'] });
            queryClient.invalidateQueries({ queryKey: ['business-information-grouped'] });
        },
    });
}

// Media
export function useKnowledgeMedia(articleUuid: string | undefined) {
    const setMedia = useKnowledgeStore((s) => s.setMedia);

    return useQuery({
        queryKey: ['knowledge-media', articleUuid],
        queryFn: async () => {
            const media = await knowledgeApi.listMedia(articleUuid!);
            setMedia(media);
            return media;
        },
        enabled: !!articleUuid,
    });
}

export function useUploadKnowledgeMedia(articleUuid: string | undefined) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UploadMediaPayload) => knowledgeApi.uploadMedia(articleUuid!, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-media', articleUuid] });
        },
    });
}

export function useDeleteKnowledgeMedia() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (mediaUuid: string) => knowledgeApi.deleteMedia(mediaUuid),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['knowledge-media'] });
        },
    });
}