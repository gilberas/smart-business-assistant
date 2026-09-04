<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Services\Customer\CustomerTagService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTagAttachController extends Controller
{
    public function __construct(
        private readonly CustomerTagService $tagService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function attach(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['required', 'string'],
        ]);

        $customer = $this->tagService->attachToCustomer(
            $business,
            $customer,
            $request->input('tags'),
            $request->user(),
        );

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.tags_attached'),
        ]);
    }

    public function detach(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['required', 'string'],
        ]);

        $customer = $this->tagService->detachFromCustomer(
            $business,
            $customer,
            $request->input('tags'),
            $request->user(),
        );

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.tags_detached'),
        ]);
    }
}
