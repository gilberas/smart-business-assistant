<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\TriggerDispatcherInterface;
use App\Jobs\Workflow\ExecuteWorkflowJob;
use App\Models\Business;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Repositories\Workflow\WorkflowRepository;
use Illuminate\Support\Facades\DB;

class TriggerDispatcherService implements TriggerDispatcherInterface
{
    public function __construct(
        private readonly WorkflowRepository $repository,
    ) {}

    public function dispatch(string $triggerType, Business $business, array $payload = []): void
    {
        $workflows = $this->findActiveWorkflowsForTrigger($triggerType, $business->id);

        foreach ($workflows as $workflow) {
            $this->executeSync($workflow, $payload, null, $triggerType);
        }
    }

    public function dispatchAsync(string $triggerType, Business $business, array $payload = []): void
    {
        $workflows = $this->findActiveWorkflowsForTrigger($triggerType, $business->id);

        foreach ($workflows as $workflow) {
            ExecuteWorkflowJob::dispatch($workflow->id, $business->id, $payload, null, $triggerType);
        }
    }

    private function findActiveWorkflowsForTrigger(string $triggerType, int $businessId): iterable
    {
        return Workflow::where('business_id', $businessId)
            ->where('status', 'active')
            ->whereHas('versions.triggers', fn ($q) => $q->where('type', $triggerType)->where('is_enabled', true))
            ->with(['versions' => fn ($q) => $q->with(['triggers', 'conditions', 'actions'])->orderByDesc('version')->take(1)])
            ->get();
    }

    private function executeSync(Workflow $workflow, array $payload, $triggeredBy, string $triggerType): void
    {
        $service = app(WorkflowExecutionService::class);
        DB::transaction(fn () => $service->execute($workflow, $payload, $triggeredBy, $triggerType));
    }
}
