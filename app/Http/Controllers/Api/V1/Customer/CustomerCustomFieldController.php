<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCustomFieldResource;
use App\Models\CustomerCustomField;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCustomFieldController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(): JsonResponse
    {
        $business = $this->business();

        $fields = CustomerCustomField::where('business_id', $business->id)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CustomerCustomFieldResource::collection($fields),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $business = $this->business();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:text,number,date,boolean,select,multi_select'],
            'options' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (CustomerCustomField::where('business_id', $business->id)
            ->where('key', $validated['key'])->exists()) {
            return response()->json([
                'message' => __('customers.custom_field_key_exists'),
            ], 422);
        }

        $validated['business_id'] = $business->id;

        $field = CustomerCustomField::create($validated);

        return response()->json([
            'data' => new CustomerCustomFieldResource($field),
            'message' => __('customers.custom_field_created'),
        ], 201);
    }

    public function destroy(Request $request, CustomerCustomField $customField): JsonResponse
    {
        $business = $this->business();

        if ($customField->business_id !== $business->id) {
            abort(403);
        }

        $customField->delete();

        return response()->json(['message' => __('customers.custom_field_deleted')]);
    }
}
