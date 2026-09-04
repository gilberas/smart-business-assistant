<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\DTOs\Analytics\DateRangeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsRequest;
use App\Services\Analytics\AnalyticsService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    private function buildFilter(AnalyticsRequest $request): AnalyticsFilterDTO
    {
        $preset = $request->input('preset', 'last_30_days');
        $dateRange = $preset === 'custom'
            ? DateRangeDTO::fromRange($request->input('start'), $request->input('end'))
            : DateRangeDTO::fromPreset($preset);

        return new AnalyticsFilterDTO(
            dateRange: $dateRange,
            status: $request->input('status'),
            channel: $request->input('channel'),
            mode: $request->input('mode'),
            agentId: $request->input('agent_id') ? (int) $request->input('agent_id') : null,
            customerId: $request->input('customer_id') ? (int) $request->input('customer_id') : null,
            campaignId: $request->input('campaign_id') ? (int) $request->input('campaign_id') : null,
            workflowId: $request->input('workflow_id') ? (int) $request->input('workflow_id') : null,
            tagIds: $request->input('tag_ids'),
            groupBy: $request->input('group_by', 'day'),
        );
    }

    public function overview(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->overview($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function conversations(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->conversations($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function customers(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->customers($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function ai(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->ai($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function agents(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->agents($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function campaigns(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->campaigns($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function workflows(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->workflows($this->business(), $filter);
        return response()->json(['data' => $data]);
    }

    public function knowledge(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);
        $data = $this->analyticsService->knowledge($this->business(), $filter);
        return response()->json(['data' => $data]);
    }
}
