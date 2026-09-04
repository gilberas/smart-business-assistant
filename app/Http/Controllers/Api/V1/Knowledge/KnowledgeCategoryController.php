<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateCategoryRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateCategoryRequest;
use App\Http\Resources\Knowledge\KnowledgeCategoryResource;
use App\Models\KnowledgeCategory;
use App\Services\Knowledge\KnowledgeCategoryService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeCategoryController extends Controller
{
    public function __construct(
        private readonly KnowledgeCategoryService $categoryService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $categories = $this->categoryService->list($business, $request->all());

        return response()->json([
            'data' => KnowledgeCategoryResource::collection($categories),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $business = $this->business();

        $categories = $this->categoryService->tree($business);

        return response()->json([
            'data' => KnowledgeCategoryResource::collection($categories),
        ]);
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $business = $this->business();

        $category = $this->categoryService->create($business, $request->validated());

        return response()->json([
            'data' => new KnowledgeCategoryResource($category),
            'message' => __('knowledge.category_created'),
        ], 201);
    }

    public function show(Request $request, KnowledgeCategory $category): JsonResponse
    {
        $business = $this->business();

        if ($category->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new KnowledgeCategoryResource($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, KnowledgeCategory $category): JsonResponse
    {
        $business = $this->business();

        if ($category->business_id !== $business->id) {
            abort(403);
        }

        $category = $this->categoryService->update($business, $category, $request->validated());

        return response()->json([
            'data' => new KnowledgeCategoryResource($category),
            'message' => __('knowledge.category_updated'),
        ]);
    }

    public function destroy(Request $request, KnowledgeCategory $category): JsonResponse
    {
        $business = $this->business();

        if ($category->business_id !== $business->id) {
            abort(403);
        }

        $this->categoryService->delete($business, $category);

        return response()->json(['message' => __('knowledge.category_deleted')]);
    }
}