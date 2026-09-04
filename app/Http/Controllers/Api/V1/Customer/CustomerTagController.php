<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreTagRequest;
use App\Http\Requests\Customer\UpdateTagRequest;
use App\Http\Resources\Customer\CustomerTagResource;
use App\Models\CustomerTag;
use App\Services\Customer\CustomerTagService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTagController extends Controller
{
    public function __construct(
        private readonly CustomerTagService $tagService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(): JsonResponse
    {
        $business = $this->business();

        return response()->json([
            'data' => CustomerTagResource::collection($this->tagService->all($business)),
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $business = $this->business();

        $tag = $this->tagService->create($business, $request->validated());

        return response()->json([
            'data' => new CustomerTagResource($tag),
            'message' => __('customers.tag_created'),
        ], 201);
    }

    public function show(Request $request, CustomerTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new CustomerTagResource($tag),
        ]);
    }

    public function update(UpdateTagRequest $request, CustomerTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        $tag = $this->tagService->update($business, $tag, $request->validated());

        return response()->json([
            'data' => new CustomerTagResource($tag),
            'message' => __('customers.tag_updated'),
        ]);
    }

    public function destroy(Request $request, CustomerTag $tag): JsonResponse
    {
        $business = $this->business();

        if ($tag->business_id !== $business->id) {
            abort(403);
        }

        $this->tagService->delete($tag);

        return response()->json(['message' => __('customers.tag_deleted')]);
    }
}
