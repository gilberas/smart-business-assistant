<?php

namespace App\Listeners\Workflow;

use App\Events\Workflow\WorkflowRunStarted;
use App\Events\Workflow\WorkflowRunCompleted;
use App\Events\Workflow\WorkflowRunFailed;
use App\Events\Workflow\WorkflowActionExecuted;
use App\Jobs\Workflow\RetryFailedWorkflowJob;
use Illuminate\Support\Facades\Log;

class WorkflowEventSubscriber
{
    public function handleRunStarted(WorkflowRunStarted $event): void
    {
        Log::info('Workflow run started', [
            'run_id' => $event->run->id,
            'workflow_id' => $event->run->workflow_id,
            'trigger_type' => $event->run->trigger_type,
        ]);
    }

    public function handleRunCompleted(WorkflowRunCompleted $event): void
    {
        Log::info('Workflow run completed', [
            'run_id' => $event->run->id,
            'workflow_id' => $event->run->workflow_id,
        ]);
    }

    public function handleRunFailed(WorkflowRunFailed $event): void
    {
        Log::error('Workflow run failed', [
            'run_id' => $event->run->id,
            'workflow_id' => $event->run->workflow_id,
            'error' => $event->exception->getMessage(),
            'will_retry' => $event->willRetry,
        ]);

        if ($event->willRetry) {
            RetryFailedWorkflowJob::dispatch($event->run->id)
                ->delay(now()->addMinutes(config('workflow.retry_delays.0', 1)));
        }
    }

    public function handleActionExecuted(WorkflowActionExecuted $event): void
    {
        if ($event->result['status'] === 'error') {
            Log::warning('Workflow action failed', [
                'run_id' => $event->run->id,
                'action_id' => $event->action->id,
                'action_type' => $event->action->type,
                'result' => $event->result,
            ]);
        }
    }

    public function subscribe(): array
    {
        return [
            WorkflowRunStarted::class => 'handleRunStarted',
            WorkflowRunCompleted::class => 'handleRunCompleted',
            WorkflowRunFailed::class => 'handleRunFailed',
            WorkflowActionExecuted::class => 'handleActionExecuted',
        ];
    }
}
