<?php

namespace App\Jobs\Workflow;

use App\Models\ScheduledWorkflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteScheduledWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $scheduledWorkflowId,
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowExecutionService $executionService): void
    {
        $scheduled = ScheduledWorkflow::with('workflow')->find($this->scheduledWorkflowId);

        if (!$scheduled || !$scheduled->is_enabled) {
            return;
        }

        $workflow = $scheduled->workflow;
        if (!$workflow || $workflow->status->value !== 'active') {
            return;
        }

        $payload = $scheduled->schedule_payload ?? [];

        $executionService->execute($workflow, $payload, null, 'scheduled');

        $scheduled->update([
            'last_run_at' => now(),
            'next_run_at' => $scheduled->cron_expression
                ? \Cron\CronExpression::factory($scheduled->cron_expression)->getNextRunDate()->format('Y-m-d H:i:s')
                : null,
        ]);
    }
}
