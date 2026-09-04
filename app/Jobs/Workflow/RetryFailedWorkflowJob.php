<?php

namespace App\Jobs\Workflow;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $workflowRunId,
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowExecutionService $executionService): void
    {
        $run = WorkflowRun::with('workflow')->find($this->workflowRunId);

        if (!$run || $run->status->value !== 'failed') {
            return;
        }

        if ($run->attempts >= $run->max_attempts) {
            return;
        }

        $workflow = $run->workflow;
        if (!$workflow || $workflow->status->value !== 'active') {
            return;
        }

        $run->update([
            'status' => 'retrying',
            'attempts' => $run->attempts + 1,
        ]);

        $triggeredByUser = $run->triggered_by ? \App\Models\User::find($run->triggered_by) : null;

        try {
            $executionService->execute($workflow, $run->input_payload ?? [], $triggeredByUser, $run->trigger_type);
        } catch (\Throwable $e) {
            $retryDelays = config('workflow.retry_delays', [1, 5, 15]);
            $nextDelay = $retryDelays[$run->attempts] ?? end($retryDelays);

            $run->update([
                'status' => $run->attempts >= $run->max_attempts ? 'failed' : 'failed',
                'next_retry_at' => $run->attempts < $run->max_attempts ? now()->addMinutes($nextDelay) : null,
            ]);

            if ($run->attempts < $run->max_attempts) {
                static::dispatch($this->workflowRunId)->delay(now()->addMinutes($nextDelay));
            }
        }
    }
}
