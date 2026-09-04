<?php

namespace App\Events\Workflow;

use App\Models\WorkflowRun;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowRunFailed
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowRun $run,
        public readonly \Throwable $exception,
        public readonly bool $willRetry = false,
    ) {}
}
