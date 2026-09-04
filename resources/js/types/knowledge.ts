export type ArticleStatus = 'draft' | 'published' | 'archived';
export type ArticleType = 'article' | 'guide' | 'faq' | 'glossary' | 'documentation';
export type ArticleVisibility = 'public' | 'team' | 'private';
export type BusinessInfoType = 'hours' | 'location' | 'contact' | 'social' | 'policy' | 'other';
export type MediaType = 'image' | 'document' | 'video';

export type KnowledgeCategory = {
    id: number;
    uuid: string;
    business_id: number;
    parent_id: number | null;
    name: string;
    slug: string | null;
    description: string | null;
    icon: string | null;
    color: string | null;
    sort_order: number;
    is_active: boolean;
    articles_count?: number;
    children?: KnowledgeCategory[];
    created_at: string;
    updated_at: string;
};

export type KnowledgeTag = {
    id: number;
    business_id: number;
    name: string;
    slug: string;
    color: string | null;
    articles_count?: number;
    created_at: string;
    updated_at: string;
};

export type KnowledgeArticle = {
    id: number;
    uuid: string;
    business_id: number;
    category_id: number | null;
    author_id: number | null;
    title: string;
    slug: string | null;
    excerpt: string | null;
    content: string | null;
    article_type: ArticleType;
    status: ArticleStatus;
    visibility: ArticleVisibility;
    featured_image: string | null;
    meta_title: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    is_featured: boolean;
    published_at: string | null;
    view_count: number;
    helpful_count: number;
    not_helpful_count: number;
    category?: KnowledgeCategory;
    tags?: KnowledgeTag[];
    author?: { id: number; name: string; avatar: string | null };
    media?: KnowledgeMedia[];
    created_at: string;
    updated_at: string;
};

export type KnowledgeArticleList = Omit<KnowledgeArticle, 'content' | 'media' | 'tags'> & {
    category?: KnowledgeCategory;
    tags?: KnowledgeTag[];
    author?: { id: number; name: string };
};

export type KnowledgeFaq = {
    id: number;
    uuid: string;
    business_id: number;
    article_id: number | null;
    category_id: number | null;
    question: string;
    answer: string | null;
    sort_order: number;
    is_published: boolean;
    category?: KnowledgeCategory;
    article?: { id: number; uuid: string; title: string; slug: string };
    created_at: string;
    updated_at: string;
};

export type KnowledgeCollection = {
    id: number;
    uuid: string;
    business_id: number;
    name: string;
    slug: string | null;
    description: string | null;
    cover_image: string | null;
    is_featured: boolean;
    sort_order: number;
    articles_count?: number;
    articles?: KnowledgeArticleList[];
    created_at: string;
    updated_at: string;
};

export type BusinessInformation = {
    id: number;
    uuid: string;
    business_id: number;
    type: BusinessInfoType;
    key: string;
    value: string | null;
    metadata: Record<string, unknown> | null;
    is_public: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
};

export type KnowledgeMedia = {
    id: number;
    uuid: string;
    business_id: number;
    article_id: number | null;
    filename: string;
    original_name: string;
    mime_type: string;
    size: number;
    disk: string;
    path: string;
    url: string | null;
    media_type: MediaType;
    created_at: string;
    updated_at: string;
};

export type KnowledgeArticleFilters = {
    status?: ArticleStatus | ArticleStatus[];
    article_type?: ArticleType | ArticleType[];
    category_id?: number;
    visibility?: ArticleVisibility | ArticleVisibility[];
    tag_ids?: number[];
    author_id?: number;
    is_featured?: boolean;
    search?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
};

export type KnowledgeFaqFilters = {
    is_published?: boolean;
    category_id?: number;
    search?: string;
    page?: number;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
};

export type KnowledgeArticleStats = {
    total: number;
    published: number;
    drafts: number;
    archived: number;
    by_type: Record<string, number>;
    by_status: Record<string, number>;
};

export type CreateArticlePayload = {
    title: string;
    slug?: string;
    excerpt?: string;
    content?: string;
    category_id?: number;
    article_type?: ArticleType;
    status?: ArticleStatus;
    visibility?: ArticleVisibility;
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
    type: BusinessInfoType;
    key: string;
    value?: string;
    metadata?: Record<string, unknown>;
    is_public?: boolean;
    sort_order?: number;
};

export type UpdateBusinessInfoPayload = Partial<CreateBusinessInfoPayload>;

export type UploadMediaPayload = {
    file: File;
    article_id?: number;
    media_type?: MediaType;
};

export type PaginatedResponse<T> = {
    data: T[];
    links: { first: string; last: string; prev: string | null; next: string | null };
    meta: { current_page: number; from: number; last_page: number; path: string; per_page: number; to: number; total: number };
};