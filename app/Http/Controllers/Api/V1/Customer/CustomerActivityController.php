<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerActivityResource;
use App\Models\Customer;
use App\Services\Customer\CustomerActivityService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerActivityController extends Controller
{
    public function __construct(
        private readonly CustomerActivityService $activityService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $limit = $request->input('limit', 50);

        return response()->json([
            'data' => CustomerActivityResource::collection(
                $this->activityService->forCustomer($business, $customer, $limit)
            ),
        ]);
    }
}
