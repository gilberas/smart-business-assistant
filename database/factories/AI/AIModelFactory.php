<?php

namespace Database\Factories\AI;

use App\Models\AI\AIModel;
use App\Models\AI\AIProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIModelFactory extends Factory
{
    protected $model = AIModel::class;

    public function definition(): array
    {
        return [
            'ai_provider_id' => AIProvider::factory(),
            'model_id' => $this->faker->unique()->word() . '-' . $this->faker->randomNumber(4),
            'name' => $this->faker->word() . ' ' . $this->faker->randomNumber(4),
            'capabilities' => ['text', 'streaming'],
            'input_price_per_1k' => $this->faker->randomFloat(10, 0.0001, 0.01),
            'output_price_per_1k' => $this->faker->randomFloat(10, 0.0001, 0.03),
            'context_window' => $this->faker->randomElement([4096, 8192, 16384, 32768, 128000]),
            'max_output_tokens' => 4096,
            'is_active' => true,
        ];
    }

    public function gpt4o(): static
    {
        return $this->state(fn(array $attributes) => [
            'model_id' => 'gpt-4o',
            'name' => 'GPT-4o',
            'capabilities' => ['text', 'structured_output', 'streaming', 'vision', 'function_calling'],
            'input_price_per_1k' => 0.0025,
            'output_price_per_1k' => 0.01,
            'context_window' => 128000,
        ]);
    }
}
