<?php

namespace App\Events\Workflow;

use App\Models\WorkflowRun;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowRunStarted
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowRun $run,
    ) {}
}
