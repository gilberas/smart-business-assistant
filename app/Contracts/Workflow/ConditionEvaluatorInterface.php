<?php

namespace App\Contracts\Workflow;

use App\Models\WorkflowCondition;

interface ConditionEvaluatorInterface
{
    public function evaluate(WorkflowCondition $condition, array $context): bool;

    public function evaluateGroup(array $conditions, array $context, string $booleanConnector = 'and'): bool;
}
