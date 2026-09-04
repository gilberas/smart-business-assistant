export type RAGSearchResult = {
    source_type: string;
    source_id: number;
    chunk_id: number | null;
    chunk_uuid: string | null;
    title: string;
    content: string;
    heading: string | null;
    relevance_score: number;
    ranking_breakdown: Record<string, number>;
    metadata: Record<string, unknown> | null;
    category: string | null;
    tags: string[];
    created_at: string | null;
    updated_at: string | null;
};

export type RAGSearchResponse = {
    query: string;
    intent: string;
    strategy: string;
    total_results: number;
    execution_time_ms: number;
    sources_used: string[];
    results: RAGSearchResult[];
};

export type RAGContextPackage = {
    query: string;
    intent: string;
    business_profile: Record<string, unknown>;
    relevant_faqs: RAGSearchResult[];
    relevant_articles: RAGSearchResult[];
    relevant_products: RAGSearchResult[];
    relevant_services: RAGSearchResult[];
    relevant_policies: RAGSearchResult[];
    relevant_documents: RAGSearchResult[];
    customer_profile: Record<string, unknown>;
    conversation_summary: Record<string, unknown>;
    message_history: { role: string; content: string }[];
    suggested_citations: { index: number; source_type: string; title: string; relevance: number }[];
    confidence_score: number;
    token_estimate: number;
    execution_time_ms: number;
    retrieval_log_id: number | null;
};

export type RAGChunk = {
    id: number;
    uuid: string;
    business_id: number;
    source_type: string;
    source_id: number;
    chunk_index: number;
    strategy: string;
    content: string;
    heading: string | null;
    metadata: Record<string, unknown> | null;
    word_count: number;
    char_count: number;
    token_estimate: number;
    is_active: boolean;
    chunkable_type: string;
    chunkable_id: number;
    created_at: string;
    updated_at: string;
};

export type RAGRetrievalLog = {
    id: number;
    business_id: number;
    user_id: number | null;
    query: string;
    parsed_query: Record<string, unknown> | null;
    intent: string;
    primary_strategy: string;
    total_results: number;
    returned_results: number;
    execution_time_ms: number;
    sources_used: string[];
    context_token_estimate: number;
    created_at: string;
};

export type QueryAnalysis = {
    parsed_query: Record<string, unknown>;
    intent: string;
    intent_label: string;
    relevant_sources: string[];
};

export type ChunkingPreview = {
    strategy: string;
    total_chunks: number;
    chunks: {
        chunk_index: number;
        content: string;
        heading: string | null;
        strategy: string;
        word_count: number;
        char_count: number;
        token_estimate: number;
    }[];
};

export type PromptPreview = {
    assembled: string;
    sections: {
        system_instruction: string;
        business_context: string;
        retrieved_context: string;
        conversation_history: string;
        user_message: string;
        output_format_instruction: string;
        total_tokens: number;
        available_tokens: number;
        was_truncated: boolean;
    };
};

export type RankingPreview = {
    input: {
        source_type: string;
        source_id: number;
        title: string;
    };
    ranking_scores: Record<string, number>;
    total_score: number;
    weights_used: Record<string, number>;
};

export type RAGSystemInfo = {
    chunking: Record<string, unknown>;
    retrieval: Record<string, unknown>;
    ranking: Record<string, unknown>;
    context: Record<string, unknown>;
    sources: string[];
};
