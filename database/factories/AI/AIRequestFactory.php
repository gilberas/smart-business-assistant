<?php

namespace Database\Factories\AI;

use App\Models\AI\AIRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIRequestFactory extends Factory
{
    protected $model = AIRequest::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'status' => 'completed',
            'prompt_tokens' => $this->faker->numberBetween(50, 500),
            'completion_tokens' => $this->faker->numberBetween(50, 500),
            'total_tokens' => fn(array $attrs) => ($attrs['prompt_tokens'] ?? 0) + ($attrs['completion_tokens'] ?? 0),
            'estimated_cost' => $this->faker->randomFloat(8, 0.001, 0.1),
            'duration_ms' => $this->faker->numberBetween(500, 10000),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
