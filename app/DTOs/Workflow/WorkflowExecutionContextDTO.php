<?php

namespace App\DTOs\Workflow;

class WorkflowExecutionContextDTO
{
    public function __construct(
        public readonly int $businessId,
        public readonly int $workflowId,
        public readonly ?int $workflowVersionId,
        public readonly string $triggerType,
        public readonly array $payload = [],
        public readonly array $variables = [],
        public readonly array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            businessId: $data['business_id'],
            workflowId: $data['workflow_id'],
            workflowVersionId: $data['workflow_version_id'] ?? null,
            triggerType: $data['trigger_type'],
            payload: $data['payload'] ?? [],
            variables: $data['variables'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }
}