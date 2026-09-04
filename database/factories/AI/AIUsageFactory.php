<?php

namespace Database\Factories\AI;

use App\Models\AI\AIUsage;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIUsageFactory extends Factory
{
    protected $model = AIUsage::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'date' => $this->faker->date(),
            'total_requests' => $this->faker->numberBetween(1, 100),
            'successful_requests' => $this->faker->numberBetween(1, 95),
            'failed_requests' => $this->faker->numberBetween(0, 5),
            'prompt_tokens' => $this->faker->numberBetween(1000, 100000),
            'completion_tokens' => $this->faker->numberBetween(500, 50000),
            'total_tokens' => fn(array $attrs) => ($attrs['prompt_tokens'] ?? 0) + ($attrs['completion_tokens'] ?? 0),
            'estimated_cost' => $this->faker->randomFloat(8, 0.01, 10),
            'total_duration_ms' => $this->faker->numberBetween(10000, 600000),
        ];
    }
}
