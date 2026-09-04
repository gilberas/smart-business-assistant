<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerExportService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerExportController extends Controller
{
    public function __construct(
        private readonly CustomerExportService $exportService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function export(Request $request): JsonResponse
    {
        $business = $this->business();

        $data = $this->exportService->export(
            $business,
            $request->user(),
            $request->all(),
        );

        return response()->json([
            'data' => $data,
            'headers' => $this->exportService->exportHeaders(),
        ]);
    }

    public function headers(): JsonResponse
    {
        return response()->json([
            'headers' => $this->exportService->exportHeaders(),
        ]);
    }
}
