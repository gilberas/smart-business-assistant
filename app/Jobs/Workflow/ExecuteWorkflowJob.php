<?php

namespace App\Jobs\Workflow;

use App\Services\Workflow\WorkflowExecutionService;
use App\Models\Workflow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public function __construct(
        public readonly int $workflowId,
        public readonly int $businessId,
        public readonly array $payload = [],
        public readonly ?int $triggeredBy = null,
        public readonly ?string $triggerType = null,
    ) {
        $this->timeout = config('workflow.limits.max_execution_time_seconds', 300);
        $this->onQueue('workflows');
    }

    public function handle(WorkflowExecutionService $executionService): void
    {
        $workflow = Workflow::where('business_id', $this->businessId)
            ->whereKey($this->workflowId)
            ->first();

        if (!$workflow || $workflow->status->value !== 'active') {
            return;
        }

        $triggeredByUser = $this->triggeredBy ? \App\Models\User::find($this->triggeredBy) : null;

        $executionService->execute($workflow, $this->payload, $triggeredByUser, $this->triggerType ?? 'system');
    }
}
