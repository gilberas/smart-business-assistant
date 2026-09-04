<?php

namespace App\Http\Controllers\Api\V1\Knowledge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KnowledgeBase\CreateBusinessInfoRequest;
use App\Http\Requests\Api\V1\KnowledgeBase\UpdateBusinessInfoRequest;
use App\Http\Resources\Knowledge\BusinessInformationResource;
use App\Models\BusinessInformation;
use App\Services\Knowledge\BusinessInformationService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessInformationController extends Controller
{
    public function __construct(
        private readonly BusinessInformationService $infoService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $info = $this->infoService->list($business, $request->all());

        return response()->json([
            'data' => BusinessInformationResource::collection($info),
        ]);
    }

    public function grouped(Request $request): JsonResponse
    {
        $business = $this->business();

        $grouped = $this->infoService->grouped($business);

        return response()->json([
            'data' => $grouped,
        ]);
    }

    public function store(CreateBusinessInfoRequest $request): JsonResponse
    {
        $business = $this->business();

        $info = $this->infoService->create($business, $request->validated());

        return response()->json([
            'data' => new BusinessInformationResource($info),
            'message' => __('knowledge.info_created'),
        ], 201);
    }

    public function show(Request $request, BusinessInformation $info): JsonResponse
    {
        $business = $this->business();

        if ($info->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new BusinessInformationResource($info),
        ]);
    }

    public function update(UpdateBusinessInfoRequest $request, BusinessInformation $info): JsonResponse
    {
        $business = $this->business();

        if ($info->business_id !== $business->id) {
            abort(403);
        }

        $info = $this->infoService->update($business, $info, $request->validated());

        return response()->json([
            'data' => new BusinessInformationResource($info),
            'message' => __('knowledge.info_updated'),
        ]);
    }

    public function destroy(Request $request, BusinessInformation $info): JsonResponse
    {
        $business = $this->business();

        if ($info->business_id !== $business->id) {
            abort(403);
        }

        $this->infoService->delete($business, $info);

        return response()->json(['message' => __('knowledge.info_deleted')]);
    }
}