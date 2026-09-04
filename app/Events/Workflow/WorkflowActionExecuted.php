<?php

namespace App\Events\Workflow;

use App\Models\WorkflowRun;
use App\Models\WorkflowAction;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowActionExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowRun $run,
        public readonly WorkflowAction $action,
        public readonly array $result,
    ) {}
}
