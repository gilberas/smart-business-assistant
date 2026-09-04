<?php

namespace Database\Factories\Workflow;

use App\Enums\Workflow\WorkflowExecutionMode;
use App\Enums\Workflow\WorkflowStatus;
use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'owner_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->randomElement(['customer_service', 'lead_management', 'support', 'notifications', 'onboarding']),
            'status' => WorkflowStatus::Draft,
            'execution_mode' => WorkflowExecutionMode::Sequential,
            'variables' => [],
            'metadata' => [],
            'version' => 1,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => WorkflowStatus::Active,
            'enabled_at' => now(),
        ]);
    }

    public function withTriggers(): static
    {
        return $this->afterCreating(function (Workflow $workflow) {
            $version = $workflow->versions()->first();
            if (!$version) {
                $version = $workflow->versions()->create([
                    'version' => 1,
                    'definition' => [],
                    'created_by' => $workflow->owner_id,
                ]);
            }

            $version->triggers()->create([
                'type' => 'manual',
                'name' => 'Manual trigger',
                'payload' => [],
                'is_enabled' => true,
            ]);

            $version->actions()->create([
                'type' => 'add_internal_note',
                'name' => 'Add note',
                'payload' => ['content' => 'Workflow executed'],
                'position' => 0,
                'is_enabled' => true,
            ]);
        });
    }
}
