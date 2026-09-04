<?php

namespace Database\Factories\AI;

use App\Models\AI\AIProvider;
use App\Models\AI\ProviderHealth;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderHealthFactory extends Factory
{
    protected $model = ProviderHealth::class;

    public function definition(): array
    {
        return [
            'ai_provider_id' => AIProvider::factory(),
            'is_healthy' => true,
            'latency_ms' => $this->faker->numberBetween(100, 3000),
            'status_code' => '200',
            'checked_at' => now(),
        ];
    }

    public function unhealthy(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_healthy' => false,
            'status_code' => '500',
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
