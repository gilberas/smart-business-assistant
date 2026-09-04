<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\Enums\RAG\SearchIntent;

class SearchStrategyFactory
{
    public function __construct(
        private readonly KeywordSearchStrategy $keywordSearch,
        private readonly FulltextSearchStrategy $fulltextSearch,
        private readonly TagSearchStrategy $tagSearch,
        private readonly CategorySearchStrategy $categorySearch,
        private readonly MetadataSearchStrategy $metadataSearch,
        private readonly HybridSearchStrategy $hybridSearch,
    ) {}

    public function make(?string $strategy = null): SearchStrategyInterface
    {
        return match ($strategy) {
            'keyword' => $this->keywordSearch,
            'fulltext' => $this->fulltextSearch,
            'tag' => $this->tagSearch,
            'category' => $this->categorySearch,
            'metadata' => $this->metadataSearch,
            'hybrid', null => $this->hybridSearch,
            default => throw new \InvalidArgumentException("Unknown search strategy: {$strategy}"),
        };
    }

    public function forIntent(?string $intent): SearchStrategyInterface
    {
        return match ($intent) {
            'product', 'pricing' => $this->make('hybrid'),
            'faq' => $this->make('keyword'),
            'policy', 'business_info' => $this->make('metadata'),
            'troubleshooting' => $this->make('fulltext'),
            'general', 'support' => $this->make('hybrid'),
            default => $this->make('hybrid'),
        };
    }

    public function all(): array
    {
        return [
            'keyword' => $this->keywordSearch,
            'fulltext' => $this->fulltextSearch,
            'tag' => $this->tagSearch,
            'category' => $this->categorySearch,
            'metadata' => $this->metadataSearch,
            'hybrid' => $this->hybridSearch,
        ];
    }

    public function available(): array
    {
        return array_keys($this->all());
    }
}
