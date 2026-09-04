<?php

namespace App\Services\RAG;

use App\Contracts\RAG\RetrievalServiceInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\PromptPackageDTO;
use App\Services\RAG\Search\SearchStrategyFactory;

class RetrievalService implements RetrievalServiceInterface
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly ContextBuilderService $contextBuilder,
        private readonly PromptBuilderService $promptBuilder,
        private readonly RankingService $rankingService,
    ) {}

    public function name(): string
    {
        return 'retrieval';
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        return $this->searchService->search($query);
    }

    public function retrieveContext(SearchQueryDTO $query): ContextPackageDTO
    {
        $response = $this->searchService->search($query);
        return $this->contextBuilder->build($query, $response->results);
    }

    public function buildPrompt(
        SearchQueryDTO $query,
        string $userMessage,
        array $promptOptions = [],
    ): PromptPackageDTO {
        $context = $this->retrieveContext($query);
        return $this->promptBuilder->build($context, $userMessage, $promptOptions);
    }

    public function analyzeQuery(string $query): array
    {
        return $this->searchService->analyzeQuery($query);
    }

    public function getSearchService(): SearchService
    {
        return $this->searchService;
    }

    public function getContextBuilder(): ContextBuilderService
    {
        return $this->contextBuilder;
    }

    public function getPromptBuilder(): PromptBuilderService
    {
        return $this->promptBuilder;
    }

    public function getRankingService(): RankingService
    {
        return $this->rankingService;
    }
}
