<?php

namespace App\Console\Commands;

use App\Models\ScheduledWorkflow;
use App\Jobs\Workflow\ExecuteScheduledWorkflowJob;
use App\Jobs\Workflow\ResumeDelayedWorkflowJob;
use App\Models\WorkflowRun;
use Illuminate\Console\Command;

class ProcessScheduledWorkflows extends Command
{
    protected $signature = 'workflows:process-scheduled';
    protected $description = 'Process scheduled and delayed workflows';

    public function handle(): int
    {
        $this->processScheduled();
        $this->processDelayed();

        return self::SUCCESS;
    }

    private function processScheduled(): void
    {
        $scheduled = ScheduledWorkflow::where('is_enabled', true)
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($scheduled as $item) {
            ExecuteScheduledWorkflowJob::dispatch($item->id);
        }

        $this->info("Dispatched {$scheduled->count()} scheduled workflows.");
    }

    private function processDelayed(): void
    {
        $delayed = WorkflowRun::where('status', 'waiting')
            ->where('next_retry_at', '<=', now())
            ->get();

        foreach ($delayed as $run) {
            ResumeDelayedWorkflowJob::dispatch($run->id);
        }

        $this->info("Resumed {$delayed->count()} delayed workflows.");
    }
}
