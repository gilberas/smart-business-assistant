<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ImportCustomersRequest;
use App\Services\Customer\CustomerImportService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class CustomerImportController extends Controller
{
    public function __construct(
        private readonly CustomerImportService $importService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function preview(ImportCustomersRequest $request): JsonResponse
    {
        $business = $this->business();

        $result = $this->importService->preview($business, $request->input('rows'));

        return response()->json($result);
    }

    public function import(ImportCustomersRequest $request): JsonResponse
    {
        $business = $this->business();

        $result = $this->importService->import(
            $business,
            $request->user(),
            $request->input('rows'),
            $request->only('update_duplicates'),
        );

        return response()->json([
            'imported' => $result['imported'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
            'message' => __('customers.import_complete', [
                'imported' => $result['imported'] ?? 0,
                'skipped' => $result['skipped'] ?? 0,
            ]),
        ]);
    }
}
