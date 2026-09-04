<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Http\Resources\AI\AIUsageResource;
use App\Services\AI\UsageService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIUsageController extends Controller
{
    public function __construct(
        private readonly UsageService $usageService,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $usage = $this->usageService->getForBusiness(
            $this->businessId(),
            $request->only(['date_from', 'date_to', 'provider']),
        );

        return response()->json([
            'data' => AIUsageResource::collection($usage['records']),
            'totals' => $usage['totals'],
            'by_provider' => $usage['by_provider'],
        ]);
    }

    public function totals(Request $request): JsonResponse
    {
        $totals = $this->usageService->getTotals(
            $this->businessId(),
            $request->only(['date_from', 'date_to', 'provider']),
        );

        return response()->json(['data' => $totals]);
    }

    public function byProvider(Request $request): JsonResponse
    {
        $byProvider = $this->usageService->getByProvider(
            $this->businessId(),
            $request->only(['date_from', 'date_to']),
        );

        return response()->json(['data' => $byProvider]);
    }
}
