<?php

namespace App\Events\Workflow;

use App\Models\Workflow;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowTriggered
{
    use Dispatchable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly string $triggerType,
        public readonly array $payload = [],
    ) {}
}
