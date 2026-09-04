<?php

namespace App\Services\RAG;

use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Enums\RAG\SearchSourceType;
use App\Services\RAG\Search\SearchStrategyFactory;
use App\Repositories\RAG\RetrievalLogRepository;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(
        private readonly SearchStrategyFactory $strategyFactory,
        private readonly QueryAnalyzerService $queryAnalyzer,
        private readonly IntentClassifierService $intentClassifier,
        private readonly RankingService $rankingService,
        private readonly RetrievalLogRepository $retrievalLogRepository,
    ) {}

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);

        $parsedQuery = $this->queryAnalyzer->analyze($query->query);
        $intent = $query->intent
            ? \App\Enums\RAG\SearchIntent::tryFrom($query->intent)
            : $this->intentClassifier->classify($query->query, $query->customerId, $query->conversationId);

        $sources = $this->resolveSources($query->sources, $intent);
        $queryWithSources = new SearchQueryDTO(
            query: $query->query,
            businessId: $query->businessId,
            customerId: $query->customerId,
            conversationId: $query->conversationId,
            intent: $intent->value,
            sources: $sources,
            tags: $query->tags,
            categories: $query->categories,
            maxResults: $query->maxResults,
            minScore: $query->minScore,
            strategy: $query->strategy ?? $this->selectStrategy($intent),
        );

        $strategyName = in_array($queryWithSources->strategy, $this->strategyFactory->available(), true)
            ? $queryWithSources->strategy
            : $this->selectStrategy($intent);

        $strategy = $this->strategyFactory->make($strategyName);
        $response = $strategy->search($queryWithSources);

        $rankedResults = $this->rankingService->rank($response->results, $query->query);
        $response = new SearchResponseDTO(
            query: $response->query,
            intent: $intent->value,
            strategy: $strategy->name(),
            results: $rankedResults,
            totalResults: count($rankedResults),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: $response->sourcesUsed,
        );

        $this->logRetrieval($query, $response, $parsedQuery, $intent->value, $strategy->name());

        return $response;
    }

    public function analyzeQuery(string $query): array
    {
        $parsed = $this->queryAnalyzer->analyze($query);
        $intent = $this->intentClassifier->classify($query);

        return [
            'parsed_query' => $parsed,
            'intent' => $intent->value,
            'intent_label' => $intent->label(),
            'relevant_sources' => $intent->relevantSources(),
        ];
    }

    private function resolveSources(array $requestedSources, ?\App\Enums\RAG\SearchIntent $intent): array
    {
        if (!empty($requestedSources)) {
            return $requestedSources;
        }

        if ($intent !== null) {
            return $intent->relevantSources();
        }

        return config('rag.sources', []);
    }

    private function selectStrategy(?\App\Enums\RAG\SearchIntent $intent): string
    {
        return match ($intent?->value) {
            'faq', 'general' => 'keyword',
            'policy', 'business_info' => 'metadata',
            'troubleshooting' => 'fulltext',
            default => config('rag.retrieval.default_search_strategy', 'hybrid'),
        };
    }

    private function logRetrieval(
        SearchQueryDTO $query,
        SearchResponseDTO $response,
        array $parsedQuery,
        string $intent,
        string $strategy,
    ): void {
        try {
            $this->retrievalLogRepository->create([
                'business_id' => $query->businessId,
                'user_id' => request()->user()?->id,
                'session_id' => (string) Str::uuid(),
                'query' => $query->query,
                'parsed_query' => $parsedQuery,
                'intent' => $intent,
                'primary_strategy' => $strategy,
                'total_results' => $response->totalResults,
                'returned_results' => count($response->results),
                'execution_time_ms' => $response->executionTimeMs,
                'sources_used' => $response->sourcesUsed,
                'top_chunk_ids' => array_map(fn(SearchResultDTO $r) => $r->chunkId, array_slice($response->results, 0, 10)),
                'context_token_estimate' => 0,
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Failed to log retrieval', ['error' => $e->getMessage()]);
        }
    }
}
