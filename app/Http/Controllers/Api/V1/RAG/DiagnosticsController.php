<?php

namespace App\Http\Controllers\Api\V1\RAG;

use App\Http\Controllers\Controller;
use App\Http\Resources\RAG\RetrievalLogResource;
use App\Repositories\RAG\RetrievalLogRepository;
use App\Services\RAG\RetrievalService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly RetrievalLogRepository $logRepository,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function logs(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['intent', 'strategy', 'date_from', 'date_to', 'per_page']), [
            'business_id' => $this->businessId(),
        ]);

        $logs = $this->logRepository->findByBusiness($this->businessId(), $filters);

        return response()->json([
            'data' => RetrievalLogResource::collection($logs),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    public function logDetail(int $id): JsonResponse
    {
        $log = $this->logRepository->findById($id);

        if (!$log || $log->business_id !== $this->businessId()) {
            abort(404);
        }

        return response()->json([
            'data' => new RetrievalLogResource($log),
        ]);
    }

    public function promptPreview(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'max:1000'],
            'user_message' => ['required', 'string', 'max:5000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
        ]);

        $query = new \App\DTOs\RAG\SearchQueryDTO(
            query: $request->input('query'),
            businessId: $this->businessId(),
            customerId: $request->input('customer_id'),
            conversationId: $request->input('conversation_id'),
        );

        $prompt = $this->retrievalService->buildPrompt(
            $query,
            $request->input('user_message'),
        );

        return response()->json([
            'data' => [
                'assembled' => $prompt->assemble(),
                'sections' => $prompt->toArray(),
            ],
        ]);
    }

    public function rankingPreview(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'max:1000'],
            'source_type' => ['required', 'string'],
            'source_id' => ['required', 'integer'],
        ]);

        $result = new \App\DTOs\RAG\SearchResultDTO(
            sourceType: $request->input('source_type'),
            sourceId: (int) $request->input('source_id'),
            title: $request->input('title', ''),
            content: $request->input('content', ''),
        );

        $score = $this->retrievalService->getRankingService()->score(
            $result,
            $request->input('query'),
        );

        return response()->json([
            'data' => [
                'input' => [
                    'source_type' => $result->sourceType,
                    'source_id' => $result->sourceId,
                    'title' => $result->title,
                ],
                'ranking_scores' => $score->toArray(),
                'total_score' => round($score->total(), 4),
                'weights_used' => config('rag.ranking.weights'),
            ],
        ]);
    }

    public function systemInfo(): JsonResponse
    {
        return response()->json([
            'data' => [
                'chunking' => [
                    'default_strategy' => config('rag.chunking.default_strategy'),
                    'strategies' => [
                        'paragraph' => ['max_size' => config('rag.chunking.paragraph.max_size')],
                        'section' => ['max_size' => config('rag.chunking.section.max_size')],
                        'sliding_window' => [
                            'size' => config('rag.chunking.sliding_window.size'),
                            'overlap' => config('rag.chunking.sliding_window.overlap'),
                        ],
                        'fixed_size' => [
                            'size' => config('rag.chunking.fixed_size.size'),
                            'overlap' => config('rag.chunking.fixed_size.overlap'),
                        ],
                    ],
                ],
                'retrieval' => [
                    'max_results_per_source' => config('rag.retrieval.max_results_per_source'),
                    'max_total_results' => config('rag.retrieval.max_total_results'),
                    'min_relevance_score' => config('rag.retrieval.min_relevance_score'),
                    'default_strategy' => config('rag.retrieval.default_search_strategy'),
                ],
                'ranking' => [
                    'weights' => config('rag.ranking.weights'),
                ],
                'context' => [
                    'max_tokens' => config('rag.context.max_tokens'),
                    'max_conversation_messages' => config('rag.context.max_conversation_messages'),
                ],
                'sources' => config('rag.sources'),
            ],
        ]);
    }
}
