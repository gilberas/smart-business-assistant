<?php

return [
    'chunking' => [
        'default_strategy' => env('RAG_CHUNK_STRATEGY', 'paragraph'),
        'fixed_size' => [
            'size' => (int) env('RAG_CHUNK_FIXED_SIZE', 512),
            'overlap' => (int) env('RAG_CHUNK_OVERLAP', 64),
        ],
        'sliding_window' => [
            'size' => (int) env('RAG_SLIDING_WINDOW_SIZE', 512),
            'overlap' => (int) env('RAG_SLIDING_WINDOW_OVERLAP', 128),
        ],
        'paragraph' => [
            'max_size' => (int) env('RAG_PARAGRAPH_MAX_SIZE', 1024),
        ],
        'section' => [
            'max_size' => (int) env('RAG_SECTION_MAX_SIZE', 2048),
        ],
    ],

    'retrieval' => [
        'max_results_per_source' => (int) env('RAG_MAX_RESULTS_PER_SOURCE', 5),
        'max_total_results' => (int) env('RAG_MAX_TOTAL_RESULTS', 20),
        'min_relevance_score' => (float) env('RAG_MIN_RELEVANCE_SCORE', 0.1),
        'default_search_strategy' => env('RAG_DEFAULT_SEARCH_STRATEGY', 'hybrid'),
    ],

    'ranking' => [
        'weights' => [
            'keyword_relevance' => (float) env('RAG_RANKING_KEYWORD', 0.30),
            'category_relevance' => (float) env('RAG_RANKING_CATEGORY', 0.15),
            'tag_relevance' => (float) env('RAG_RANKING_TAG', 0.10),
            'popularity' => (float) env('RAG_RANKING_POPULARITY', 0.10),
            'recency' => (float) env('RAG_RANKING_RECENCY', 0.15),
            'business_priority' => (float) env('RAG_RANKING_PRIORITY', 0.20),
        ],
    ],

    'context' => [
        'max_tokens' => (int) env('RAG_CONTEXT_MAX_TOKENS', 4000),
        'include_business_profile' => (bool) env('RAG_INCLUDE_BUSINESS_PROFILE', true),
        'include_customer_profile' => (bool) env('RAG_INCLUDE_CUSTOMER_PROFILE', true),
        'include_conversation_history' => (bool) env('RAG_INCLUDE_CONVERSATION_HISTORY', true),
        'max_conversation_messages' => (int) env('RAG_MAX_CONVERSATION_MESSAGES', 10),
    ],

    'prompt' => [
        'max_tokens' => (int) env('RAG_PROMPT_MAX_TOKENS', 8000),
        'system_instruction' => env('RAG_SYSTEM_INSTRUCTION', 'You are a helpful AI assistant for a business. Answer questions accurately using the provided context.'),
        'output_format_instruction' => env('RAG_OUTPUT_FORMAT_INSTRUCTION', 'Provide a clear, concise response based on the context. If the context does not contain enough information, say so.'),
    ],

    'cache' => [
        'search_ttl' => (int) env('RAG_CACHE_SEARCH_TTL', 300),
        'context_ttl' => (int) env('RAG_CACHE_CONTEXT_TTL', 60),
        'chunk_ttl' => (int) env('RAG_CACHE_CHUNK_TTL', 3600),
    ],

    'sources' => [
        'business_information',
        'knowledge_article',
        'faq',
        'product',
        'service',
        'policy',
        'document',
        'conversation',
        'customer',
        'order_history',
        'booking_history',
    ],
];
