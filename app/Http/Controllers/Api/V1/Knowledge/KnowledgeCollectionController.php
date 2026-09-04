<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateCollectionRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateCollectionRequest;
use App\Http\Resources\Knowledge\KnowledgeCollectionDetailResource;
use App\Http\Resources\Knowledge\KnowledgeCollectionResource;
use App\Models\KnowledgeCollection;
use App\Services\Knowledge\KnowledgeCollectionService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeCollectionController extends Controller
{
    public function __construct(
        private readonly KnowledgeCollectionService $collectionService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $collections = $this->collectionService->list($business);

        return response()->json([
            'data' => KnowledgeCollectionResource::collection($collections),
        ]);
    }

    public function store(CreateCollectionRequest $request): JsonResponse
    {
        $business = $this->business();

        $collection = $this->collectionService->create($business, $request->validated());

        return response()->json([
            'data' => new KnowledgeCollectionResource($collection),
            'message' => __('knowledge.collection_created'),
        ], 201);
    }

    public function show(Request $request, KnowledgeCollection $collection): JsonResponse
    {
        $business = $this->business();

        if ($collection->business_id !== $business->id) {
            abort(403);
        }

        $collection->load('articles');

        return response()->json([
            'data' => new KnowledgeCollectionDetailResource($collection),
        ]);
    }

    public function update(UpdateCollectionRequest $request, KnowledgeCollection $collection): JsonResponse
    {
        $business = $this->business();

        if ($collection->business_id !== $business->id) {
            abort(403);
        }

        $collection = $this->collectionService->update($business, $collection, $request->validated());

        return response()->json([
            'data' => new KnowledgeCollectionResource($collection),
            'message' => __('knowledge.collection_updated'),
        ]);
    }

    public function destroy(Request $request, KnowledgeCollection $collection): JsonResponse
    {
        $business = $this->business();

        if ($collection->business_id !== $business->id) {
            abort(403);
        }

        $this->collectionService->delete($business, $collection);

        return response()->json(['message' => __('knowledge.collection_deleted')]);
    }
}