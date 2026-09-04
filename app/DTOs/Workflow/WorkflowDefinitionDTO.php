<?php

namespace App\DTOs\Workflow;

use App\Enums\Workflow\WorkflowExecutionMode;
use App\Enums\Workflow\WorkflowStatus;

class WorkflowDefinitionDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $category,
        public readonly WorkflowStatus $status,
        public readonly WorkflowExecutionMode $executionMode,
        public readonly array $variables = [],
        public readonly array $metadata = [],
        public readonly array $triggers = [],
        public readonly array $conditions = [],
        public readonly array $actions = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            category: $data['category'],
            status: WorkflowStatus::from($data['status'] ?? 'draft'),
            executionMode: WorkflowExecutionMode::from($data['execution_mode'] ?? 'sequential'),
            variables: $data['variables'] ?? [],
            metadata: $data['metadata'] ?? [],
            triggers: $data['triggers'] ?? [],
            conditions: $data['conditions'] ?? [],
            actions: $data['actions'] ?? [],
        );
    }
}