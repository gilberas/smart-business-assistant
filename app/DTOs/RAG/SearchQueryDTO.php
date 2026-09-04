<?php

namespace App\DTOs\RAG;

readonly class SearchQueryDTO
{
    public function __construct(
        public string $query,
        public int $businessId,
        public ?int $customerId = null,
        public ?int $conversationId = null,
        public ?string $intent = null,
        public array $sources = [],
        public array $tags = [],
        public array $categories = [],
        public array $metadata = [],
        public int $maxResults = 10,
        public float $minScore = 0.1,
        public ?string $strategy = null,
    ) {}

    public static function fromRequest(array $data, int $businessId): self
    {
        return new self(
            query: $data['query'],
            businessId: $businessId,
            customerId: $data['customer_id'] ?? null,
            conversationId: $data['conversation_id'] ?? null,
            intent: $data['intent'] ?? null,
            sources: $data['sources'] ?? [],
            tags: $data['tags'] ?? [],
            categories: $data['categories'] ?? [],
            metadata: $data['metadata'] ?? [],
            maxResults: (int) ($data['max_results'] ?? config('rag.retrieval.max_results_per_source', 5)),
            minScore: (float) ($data['min_score'] ?? config('rag.retrieval.min_relevance_score', 0.1)),
            strategy: $data['strategy'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'business_id' => $this->businessId,
            'customer_id' => $this->customerId,
            'conversation_id' => $this->conversationId,
            'intent' => $this->intent,
            'sources' => $this->sources,
            'tags' => $this->tags,
            'categories' => $this->categories,
            'metadata' => $this->metadata,
            'max_results' => $this->maxResults,
            'min_score' => $this->minScore,
            'strategy' => $this->strategy,
        ];
    }
}
