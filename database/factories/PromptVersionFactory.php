<?php

namespace Database\Factories;

use App\Models\PromptTemplate;
use App\Models\PromptVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromptVersionFactory extends Factory
{
    protected $model = PromptVersion::class;

    public function definition(): array
    {
        return [
            'prompt_template_id' => PromptTemplate::factory(),
            'version' => 1,
            'content' => $this->faker->paragraph(),
            'changelog' => 'Initial version',
            'variables' => ['business_name'],
        ];
    }
}
