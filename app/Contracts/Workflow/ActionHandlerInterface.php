<?php

namespace App\Contracts\Workflow;

use App\Models\WorkflowAction;
use App\Models\WorkflowRun;

interface ActionHandlerInterface
{
    public function execute(WorkflowRun $run, WorkflowAction $action, array $context): array;

    public function supports(string $actionType): bool;
}
