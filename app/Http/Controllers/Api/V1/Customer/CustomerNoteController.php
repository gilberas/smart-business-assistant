<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerNoteRequest;
use App\Http\Requests\Customer\UpdateCustomerNoteRequest;
use App\Http\Resources\Customer\CustomerNoteResource;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Services\Customer\CustomerNoteService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNoteController extends Controller
{
    public function __construct(
        private readonly CustomerNoteService $noteService,
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

        return response()->json([
            'data' => CustomerNoteResource::collection(
                $this->noteService->forCustomer($business, $customer)
            ),
        ]);
    }

    public function store(StoreCustomerNoteRequest $request, Customer $customer): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id) {
            abort(403);
        }

        $note = $this->noteService->create(
            $business,
            $customer,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => new CustomerNoteResource($note),
            'message' => __('customers.note_created'),
        ], 201);
    }

    public function update(UpdateCustomerNoteRequest $request, Customer $customer, CustomerNote $note): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id || $note->customer_id !== $customer->id) {
            abort(403);
        }

        $note = $this->noteService->update($business, $customer, $note, $request->validated());

        return response()->json([
            'data' => new CustomerNoteResource($note),
            'message' => __('customers.note_updated'),
        ]);
    }

    public function destroy(Request $request, Customer $customer, CustomerNote $note): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id || $note->customer_id !== $customer->id) {
            abort(403);
        }

        $this->noteService->delete($business, $customer, $note);

        return response()->json(['message' => __('customers.note_deleted')]);
    }

    public function togglePin(Request $request, Customer $customer, CustomerNote $note): JsonResponse
    {
        $business = $this->business();

        if ($customer->business_id !== $business->id || $note->customer_id !== $customer->id) {
            abort(403);
        }

        $note = $this->noteService->togglePin($business, $customer, $note);

        return response()->json([
            'data' => new CustomerNoteResource($note),
            'message' => $note->is_pinned ? __('customers.note_pinned') : __('customers.note_unpinned'),
        ]);
    }
}
