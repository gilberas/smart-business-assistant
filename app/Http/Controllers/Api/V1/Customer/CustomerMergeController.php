<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MergeCustomersRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Services\Customer\CustomerService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class CustomerMergeController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function merge(MergeCustomersRequest $request): JsonResponse
    {
        $business = $this->business();

        $primary = $this->customerService->get($business, $request->input('primary_id'));
        $duplicate = $this->customerService->get($business, $request->input('duplicate_id'));

        $customer = $this->customerService->merge(
            $business,
            $request->user(),
            $primary,
            $duplicate,
        );

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.merged'),
        ]);
    }
}
