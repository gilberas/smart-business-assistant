<?php

namespace Database\Factories\AI;

use App\Models\AI\AIConfiguration;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIConfigurationFactory extends Factory
{
    protected $model = AIConfiguration::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'top_p' => 1.0,
            'max_tokens' => 2048,
            'timeout_seconds' => 30,
            'streaming' => false,
            'language' => 'en',
            'is_active' => true,
        ];
    }
}
