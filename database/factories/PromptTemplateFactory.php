<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\PromptTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromptTemplateFactory extends Factory
{
    protected $model = PromptTemplate::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(3),
            'type' => 'system',
            'content' => 'You are a helpful assistant for {{business_name}}.',
            'description' => $this->faker->sentence(),
            'version' => 1,
            'is_active' => true,
        ];
    }

    public function system(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'system',
            'content' => 'You are an AI assistant for {{business_name}}. Respond in {{language}}.',
        ]);
    }

    public function classification(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'classification',
            'content' => 'Classify the following message into one of these categories: {{categories}}.',
        ]);
    }
}
