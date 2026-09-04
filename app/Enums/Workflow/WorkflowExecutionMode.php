<?php

namespace App\Enums\Workflow;

enum WorkflowExecutionMode: string
{
    case Sequential = 'sequential';
    case Parallel = 'parallel';

    public function label(): string
    {
        return match ($this) {
            self::Sequential => 'Sequential',
            self::Parallel => 'Parallel',
        };
    }
}