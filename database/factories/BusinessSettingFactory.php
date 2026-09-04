<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'business_hours' => [
                'monday' => ['09:00-18:00'],
                'tuesday' => ['09:00-18:00'],
                'wednesday' => ['09:00-18:00'],
                'thursday' => ['09:00-18:00'],
                'friday' => ['09:00-18:00'],
                'saturday' => ['10:00-14:00'],
                'sunday' => [],
            ],
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4o',
            'ai_temperature' => 0.7,
            'ai_max_tokens' => 2048,
        ];
    }
}
