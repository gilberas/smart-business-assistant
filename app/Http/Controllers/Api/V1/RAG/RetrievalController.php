<?php

namespace App\Http\Controllers\Api\V1\RAG;

use App\Http\Controllers\Controller;
use App\Http\Requests\RAG\SearchRequest;
use App\Http\Resources\RAG\SearchResultResource;
use App\DTOs\RAG\SearchQueryDTO;
use App\Services\RAG\RetrievalService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class RetrievalController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $query = SearchQueryDTO::fromRequest($request->validated(), $this->businessId());
        $response = $this->retrievalService->search($query);

        return response()->json([
            'data' => [
                'query' => $response->query,
                'intent' => $response->intent,
                'strategy' => $response->strategy,
                'total_results' => $response->totalResults,
                'execution_time_ms' => $response->executionTimeMs,
                'sources_used' => $response->sourcesUsed,
                'results' => SearchResultResource::collection(collect($response->results)),
            ],
        ]);
    }

    public function contextPreview(SearchRequest $request): JsonResponse
    {
        $query = SearchQueryDTO::fromRequest($request->validated(), $this->businessId());
        $context = $this->retrievalService->retrieveContext($query);

        return response()->json([
            'data' => $context->toArray(),
        ]);
    }

    public function analyzeQuery(SearchRequest $request): JsonResponse
    {
        $analysis = $this->retrievalService->analyzeQuery($request->input('query'));

        return response()->json([
            'data' => $analysis,
        ]);
    }

    public function strategies(): JsonResponse
    {
        $strategies = $this->retrievalService->getSearchService()->analyzeQuery('');

        return response()->json([
            'data' => [
                'available_strategies' => [
                    'keyword' => 'Keyword search against titles and content',
                    'fulltext' => 'MySQL FULLTEXT search on knowledge chunks',
                    'tag' => 'Search by tag names',
                    'category' => 'Search by category',
                    'metadata' => 'Search business information by key/value',
                    'hybrid' => 'Combined multi-strategy search',
                ],
                'available_sources' => config('rag.sources', []),
                'default_strategy' => config('rag.retrieval.default_search_strategy', 'hybrid'),
                'chunking_strategies' => [
                    'paragraph' => 'Split by paragraph boundaries (default)',
                    'section' => 'Split by headings/sections',
                    'sliding_window' => 'Overlapping windows',
                    'fixed_size' => 'Fixed-size chunks',
                ],
            ],
        ]);
    }
}
