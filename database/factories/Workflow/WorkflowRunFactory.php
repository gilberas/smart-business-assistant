<?php

namespace Database\Factories\Workflow;

use App\Models\WorkflowRun;
use App\Models\Workflow;
use App\Models\Business;
use App\Enums\Workflow\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowRunFactory extends Factory
{
    protected $model = WorkflowRun::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'workflow_version_id' => null,
            'business_id' => Business::factory(),
            'triggered_by' => null,
            'trigger_type' => 'manual',
            'status' => WorkflowRunStatus::Pending,
            'execution_mode' => 'sequential',
            'attempts' => 0,
            'max_attempts' => 3,
            'input_payload' => [],
            'context' => [],
            'result' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => WorkflowRunStatus::Completed,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => WorkflowRunStatus::Failed,
            'attempts' => 3,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }
}
