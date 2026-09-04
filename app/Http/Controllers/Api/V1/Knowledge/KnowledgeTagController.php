<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateTagRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateTagRequest;
use App\Http\Resources\Knowledge\KnowledgeTagResource;
use App\Models\KnowledgeTag;
use App\Services\Knowledge\KnowledgeTagService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeTagController extends Controller
{
    public function __construct(
        private readonly KnowledgeTagService $tagService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $tags = $this->tagService->list($business);

        return response()->json([
            'data' => KnowledgeTagResource::collection($tags),
        ]);
    }

    public function store(CreateTagRequest $request): JsonResponse
    {
        $business = $this->business();

        $tag = $this->tagService->create($business, $request->validated());

        return response()->json([
            'data' => new KnowledgeTagResource($tag),
            'message' => __('knowledge.tag_created'),
        ], 201);
    }

    public function show(Request $request, KnowledgeTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new KnowledgeTagResource($tag),
        ]);
    }

    public function update(UpdateTagRequest $request, KnowledgeTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        $tag = $this->tagService->update($business, $tag, $request->validated());

        return response()->json([
            'data' => new KnowledgeTagResource($tag),
            'message' => __('knowledge.tag_updated'),
        ]);
    }

    public function destroy(Request $request, KnowledgeTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        $this->tagService->delete($business, $tag);

        return response()->json(['message' => __('knowledge.tag_deleted')]);
    }
}