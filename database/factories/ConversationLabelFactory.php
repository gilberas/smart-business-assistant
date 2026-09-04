<?php

namespace Database\Factories;

use App\Models\ConversationLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationLabelFactory extends Factory
{
    protected $model = ConversationLabel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'description' => fake()->sentence(),
        ];
    }
}
