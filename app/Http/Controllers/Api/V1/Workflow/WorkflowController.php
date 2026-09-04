<?php

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Contracts\Workflow\WorkflowServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\ImportWorkflowRequest;
use App\Http\Requests\Workflow\StoreWorkflowRequest;
use App\Http\Requests\Workflow\UpdateWorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\TenantService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly WorkflowExecutionService $executionService,
        private readonly TenantService $tenantService,
    ) {}

    private function businessId(): int
    {
        return $this->tenantService->currentOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $workflows = $this->workflowService->paginate(
            $this->tenantService->currentOrFail(),
            $request->only(['status', 'category', 'search']),
            (int) $request->input('per_page', 15),
        );

        return response()->json(['data' => $workflows->items(), 'meta' => [
            'current_page' => $workflows->currentPage(),
            'last_page' => $workflows->lastPage(),
            'per_page' => $workflows->perPage(),
            'total' => $workflows->total(),
        ]]);
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $workflow = $this->workflowService->create(
            $this->tenantService->currentOrFail(),
            $request->user(),
            $request->validated(),
        );

        return response()->json(['data' => $workflow->load(['owner', 'versions', 'variablesDefinition'])], 201);
    }

    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json(['data' => $this->workflowService->find($this->tenantService->currentOrFail(), $workflow->id)]);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        return response()->json(['data' => $this->workflowService->update($workflow, $request->validated())]);
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);

        $this->workflowService->delete($workflow);

        return response()->json(['message' => 'Workflow deleted']);
    }

    public function enable(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        return response()->json(['data' => $this->workflowService->enable($workflow)]);
    }

    public function disable(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        return response()->json(['data' => $this->workflowService->disable($workflow)]);
    }

    public function execute(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('execute', $workflow);

        $run = $this->executionService->execute(
            $workflow,
            $request->input('payload', []),
            $request->user(),
            $request->input('trigger_type', 'manual'),
        );

        return response()->json(['data' => $run]);
    }

    public function versions(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json(['data' => $this->workflowService->versions($workflow)]);
    }

    public function runs(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $runs = $this->workflowService->runs($workflow, (int) $request->input('per_page', 20));

        return response()->json(['data' => $runs->items(), 'meta' => [
            'current_page' => $runs->currentPage(),
            'last_page' => $runs->lastPage(),
            'per_page' => $runs->perPage(),
            'total' => $runs->total(),
        ]]);
    }

    public function runShow(WorkflowRun $run): JsonResponse
    {
        $this->authorize('view', $run->workflow);

        return response()->json(['data' => $run->load(['logs', 'workflow', 'version'])]);
    }

    public function runRetry(WorkflowRun $run): JsonResponse
    {
        $this->authorize('execute', $run->workflow);

        $newRun = $this->executionService->retry($run);

        return response()->json(['data' => $newRun]);
    }

    public function runCancel(WorkflowRun $run): JsonResponse
    {
        $this->authorize('update', $run->workflow);

        $run->update(['status' => 'cancelled', 'finished_at' => now()]);

        return response()->json(['message' => 'Run cancelled']);
    }

    public function logs(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $runId = $request->integer('run_id');
        $run = $workflow->runs()->findOrFail($runId);
        $logs = $this->workflowService->logs($run, (int) $request->input('per_page', 50));

        return response()->json(['data' => $logs->items(), 'meta' => [
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]]);
    }

    public function export(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json(['data' => $this->workflowService->export($workflow)]);
    }

    public function import(ImportWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $workflow = $this->workflowService->import(
            $this->tenantService->currentOrFail(),
            $request->user(),
            $request->validated(),
        );

        return response()->json(['data' => $workflow->load(['owner', 'versions', 'variablesDefinition'])], 201);
    }
}