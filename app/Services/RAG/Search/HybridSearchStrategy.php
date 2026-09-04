<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;

class HybridSearchStrategy implements SearchStrategyInterface
{
    public function __construct(
        private readonly KeywordSearchStrategy $keywordSearch,
        private readonly FulltextSearchStrategy $fulltextSearch,
        private readonly TagSearchStrategy $tagSearch,
        private readonly CategorySearchStrategy $categorySearch,
        private readonly MetadataSearchStrategy $metadataSearch,
    ) {}

    public function name(): string
    {
        return 'hybrid';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return strlen(trim($query->query)) >= 1;
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $allResults = [];
        $sourcesUsed = [];
        $errors = [];
        $strategies = [];

        if ($this->keywordSearch->supports($query)) {
            $strategies[] = $this->keywordSearch;
        }
        if ($this->fulltextSearch->supports($query)) {
            $strategies[] = $this->fulltextSearch;
        }
        if ($this->tagSearch->supports($query)) {
            $strategies[] = $this->tagSearch;
        }
        if ($this->categorySearch->supports($query)) {
            $strategies[] = $this->categorySearch;
        }
        if ($this->metadataSearch->supports($query)) {
            $strategies[] = $this->metadataSearch;
        }

        if (empty($strategies)) {
            return new SearchResponseDTO(
                query: $query->query,
                strategy: $this->name(),
                executionTimeMs: (microtime(true) - $start) * 1000,
            );
        }

        foreach ($strategies as $strategy) {
            try {
                $result = $strategy->search($query);
                $allResults = array_merge($allResults, $result->results);
                $sourcesUsed = array_unique(array_merge($sourcesUsed, $result->sourcesUsed));
            } catch (\Throwable $e) {
                $errors[] = $strategy->name() . ': ' . $e->getMessage();
            }
        }

        $seen = [];
        $deduplicated = [];
        foreach ($allResults as $result) {
            $key = $result->sourceType . ':' . $result->sourceId;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $result;
            } elseif ($result->relevanceScore > 0) {
                foreach ($deduplicated as &$existing) {
                    if ($existing->sourceType === $result->sourceType && $existing->sourceId === $result->sourceId) {
                        $existing = $result;
                        break;
                    }
                }
            }
        }

        usort($deduplicated, fn($a, $b) => $b->relevanceScore <=> $a->relevanceScore);

        return new SearchResponseDTO(
            query: $query->query,
            strategy: $this->name(),
            results: array_slice($deduplicated, 0, $query->maxResults),
            totalResults: count($deduplicated),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: $sourcesUsed,
            errors: $errors,
        );
    }
}
