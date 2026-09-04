<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessSettingsRequest;
use App\Http\Resources\Business\BusinessSettingsResource;
use App\Services\BusinessService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
        private readonly TenantService $tenantService,
    ) {}

    public function show(Request $request, int $businessId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);

        $settings = $this->businessService->getSettings($business);

        return response()->json(new BusinessSettingsResource($settings));
    }

    public function update(UpdateBusinessSettingsRequest $request, int $businessId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);

        $settings = $this->businessService->updateSettings(
            $request->user(),
            $business,
            $request->validated(),
        );

        return response()->json([
            'settings' => new BusinessSettingsResource($settings),
            'message' => __('business.settings_updated'),
        ]);
    }
}
