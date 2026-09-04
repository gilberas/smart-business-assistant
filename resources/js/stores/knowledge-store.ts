import { create } from 'zustand';
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
} from '@/types/knowledge';

interface KnowledgeState {
    articles: KnowledgeArticleList[];
    selectedArticle: KnowledgeArticle | null;
    categories: KnowledgeCategory[];
    tags: KnowledgeTag[];
    faqs: KnowledgeFaq[];
    collections: KnowledgeCollection[];
    selectedCollection: KnowledgeCollectionDetail | null;
    businessInfo: BusinessInformation[];
    groupedBusinessInfo: Record<string, BusinessInformation[]>;
    media: KnowledgeMedia[];
    stats: KnowledgeArticleStats | null;
    isLoading: boolean;

    setArticles: (articles: KnowledgeArticleList[]) => void;
    setSelectedArticle: (article: KnowledgeArticle | null) => void;
    setCategories: (categories: KnowledgeCategory[]) => void;
    setTags: (tags: KnowledgeTag[]) => void;
    setFaqs: (faqs: KnowledgeFaq[]) => void;
    setCollections: (collections: KnowledgeCollection[]) => void;
    setSelectedCollection: (collection: KnowledgeCollectionDetail | null) => void;
    setBusinessInfo: (info: BusinessInformation[]) => void;
    setGroupedBusinessInfo: (info: Record<string, BusinessInformation[]>) => void;
    setMedia: (media: KnowledgeMedia[]) => void;
    setStats: (stats: KnowledgeArticleStats | null) => void;
    setLoading: (loading: boolean) => void;
    removeArticle: (uuid: string) => void;
    removeCategory: (uuid: string) => void;
    removeTag: (uuid: string) => void;
    removeFaq: (uuid: string) => void;
    removeCollection: (uuid: string) => void;
    removeBusinessInfo: (uuid: string) => void;
}

const initialFilters: KnowledgeArticleFilters = {};

export const useKnowledgeStore = create<KnowledgeState>()((set) => ({
    articles: [],
    selectedArticle: null,
    categories: [],
    tags: [],
    faqs: [],
    collections: [],
    selectedCollection: null,
    businessInfo: [],
    groupedBusinessInfo: {},
    media: [],
    stats: null,
    isLoading: false,

    setArticles: (articles) => set({ articles }),
    setSelectedArticle: (article) => set({ selectedArticle: article }),
    setCategories: (categories) => set({ categories }),
    setTags: (tags) => set({ tags }),
    setFaqs: (faqs) => set({ faqs }),
    setCollections: (collections) => set({ collections }),
    setSelectedCollection: (collection) => set({ selectedCollection: collection }),
    setBusinessInfo: (info) => set({ businessInfo: info }),
    setGroupedBusinessInfo: (info) => set({ groupedBusinessInfo: info }),
    setMedia: (media) => set({ media }),
    setStats: (stats) => set({ stats }),
    setLoading: (isLoading) => set({ isLoading }),
    removeArticle: (uuid) =>
        set((state) => ({
            articles: state.articles.filter((a) => a.uuid !== uuid),
            selectedArticle: state.selectedArticle?.uuid === uuid ? null : state.selectedArticle,
        })),
    removeCategory: (uuid) =>
        set((state) => ({
            categories: state.categories.filter((c) => c.uuid !== uuid),
        })),
    removeTag: (uuid) =>
        set((state) => ({
            tags: state.tags.filter((t) => t.uuid !== uuid),
        })),
    removeFaq: (uuid) =>
        set((state) => ({
            faqs: state.faqs.filter((f) => f.uuid !== uuid),
        })),
    removeCollection: (uuid) =>
        set((state) => ({
            collections: state.collections.filter((c) => c.uuid !== uuid),
            selectedCollection: state.selectedCollection?.uuid === uuid ? null : state.selectedCollection,
        })),
    removeBusinessInfo: (uuid) =>
        set((state) => ({
            businessInfo: state.businessInfo.filter((b) => b.uuid !== uuid),
        })),
}));