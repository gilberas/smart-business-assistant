<?php

namespace App\Events\Workflow;

use App\Models\WorkflowRun;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowRunCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowRun $run,
    ) {}
}
