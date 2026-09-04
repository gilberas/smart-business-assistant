<?php

namespace Database\Factories\AI;

use App\Models\AI\AIProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIProviderFactory extends Factory
{
    protected $model = AIProvider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['OpenAI', 'Anthropic Claude', 'Google Gemini']),
            'slug' => $this->faker->unique()->randomElement(['openai', 'anthropic', 'google']),
            'base_url' => $this->faker->url(),
            'api_key' => 'sk-' . $this->faker->sha256(),
            'is_active' => true,
            'is_default' => false,
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function openai(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'OpenAI',
            'slug' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'is_default' => true,
            'priority' => 0,
        ]);
    }
}
