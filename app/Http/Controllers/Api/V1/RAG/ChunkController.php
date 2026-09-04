<?php

namespace App\Http\Controllers\Api\V1\RAG;

use App\Http\Controllers\Controller;
use App\Http\Resources\RAG\ChunkResource;
use App\Models\RAG\KnowledgeChunk;
use App\Repositories\RAG\KnowledgeChunkRepository;
use App\Services\RAG\ChunkingService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChunkController extends Controller
{
    public function __construct(
        private readonly KnowledgeChunkRepository $chunkRepository,
        private readonly ChunkingService $chunkingService,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['source_type', 'strategy', 'search', 'per_page']), [
            'business_id' => $this->businessId(),
        ]);

        $chunks = $this->chunkRepository->findByBusiness($this->businessId(), $filters);

        return response()->json([
            'data' => ChunkResource::collection($chunks),
            'meta' => [
                'total' => $chunks->total(),
                'per_page' => $chunks->perPage(),
                'current_page' => $chunks->currentPage(),
                'last_page' => $chunks->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $chunk = $this->chunkRepository->findById($id);

        if (!$chunk || $chunk->business_id !== $this->businessId()) {
            abort(404);
        }

        return response()->json([
            'data' => new ChunkResource($chunk),
        ]);
    }

    public function bySource(Request $request, string $sourceType, ?int $sourceId = null): JsonResponse
    {
        $chunks = $this->chunkRepository->findBySource($this->businessId(), $sourceType, $sourceId);

        return response()->json([
            'data' => ChunkResource::collection($chunks),
            'meta' => ['total' => $chunks->count()],
        ]);
    }

    public function stats(): JsonResponse
    {
        $businessId = $this->businessId();
        $totalChunks = $this->chunkRepository->countByBusiness($businessId);

        $sourceCounts = [];
        foreach (config('rag.sources', []) as $source) {
            $count = $this->chunkRepository->countBySource($businessId, $source);
            if ($count > 0) {
                $sourceCounts[$source] = $count;
            }
        }

        return response()->json([
            'data' => [
                'total_chunks' => $totalChunks,
                'by_source' => $sourceCounts,
                'business_id' => $businessId,
            ],
        ]);
    }

    public function previewChunking(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:50000'],
            'strategy' => ['nullable', 'string', 'in:paragraph,section,sliding_window,fixed_size'],
        ]);

        $content = $request->input('content');
        $strategy = $request->input('strategy');
        $chunks = $this->chunkingService->chunk($content, $strategy);

        return response()->json([
            'data' => [
                'strategy' => $strategy ?? config('rag.chunking.default_strategy', 'paragraph'),
                'total_chunks' => count($chunks),
                'chunks' => array_map(fn($c) => $c->toArray(), $chunks),
            ],
        ]);
    }
}
