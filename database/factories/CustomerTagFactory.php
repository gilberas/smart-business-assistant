<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\CustomerTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerTagFactory extends Factory
{
    protected $model = CustomerTag::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
