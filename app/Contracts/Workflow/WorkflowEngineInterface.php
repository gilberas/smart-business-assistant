<?php

namespace App\Contracts\Workflow;

use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;

interface WorkflowEngineInterface
{
    public function execute(Workflow $workflow, array $payload = [], ?User $triggeredBy = null, ?string $triggerType = null): void;

    public function executeById(int $workflowId, int $businessId, array $payload = [], ?User $triggeredBy = null, ?string $triggerType = null): void;

    public function executeForTrigger(string $triggerType, Business $business, array $payload = []): void;
}
