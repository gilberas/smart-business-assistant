<?php

namespace App\Jobs\Workflow;

use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResumeDelayedWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $workflowRunId,
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowExecutionService $executionService): void
    {
        $run = WorkflowRun::with(['workflow', 'version.actions'])->find($this->workflowRunId);

        if (!$run || $run->status->value !== 'waiting') {
            return;
        }

        $run->update(['status' => 'running']);

        $version = $run->version;
        if (!$version) {
            $run->update(['status' => 'failed', 'finished_at' => now()]);
            return;
        }

        $context = $run->context ?? [];
        $actions = $version->actions
            ->where('is_enabled', true)
            ->sortBy('position')
            ->values();

        $started = false;
        foreach ($actions as $action) {
            if (!$started && $action->type === 'delay_execution') {
                $started = true;
                continue;
            }
            if (!$started) {
                continue;
            }
            $executionService->executeAction($run, $action, $context);
        }

        $run->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }
}
