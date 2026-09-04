<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\Customer\CustomerListResource;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Services\Customer\CustomerService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $customers = $this->customerService->list($business, $request->all());

        return response()->json(
            CustomerListResource::collection($customers)
                ->response()
                ->getData()
        );
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $business = $this->business();

        $customer = $this->customerService->create(
            $business,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.created'),
        ], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $customer->load(['tags', 'addresses', 'primaryAddress', 'customFieldValues.customField']);

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $customer = $this->customerService->update(
            $business,
            $request->user(),
            $customer,
            $request->validated(),
        );

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.updated'),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $this->customerService->delete($business, $request->user(), $customer);

        return response()->json(['message' => __('customers.deleted')]);
    }

    public function archive(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $customer = $this->customerService->archive($business, $request->user(), $customer);

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.archived'),
        ]);
    }

    public function restore(Request $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $customer = $this->customerService->restore($business, $request->user(), $customer);

        return response()->json([
            'data' => new CustomerResource($customer),
            'message' => __('customers.restored'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $business = $this->business();

        $customers = $this->customerService->search(
            $business,
            $request->input('q', ''),
            $request->input('limit', 10),
        );

        return response()->json([
            'data' => CustomerListResource::collection($customers),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $business = $this->business();

        return response()->json(
            $this->customerService->stats($business)
        );
    }
}
