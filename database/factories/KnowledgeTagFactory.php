<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\KnowledgeTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeTagFactory extends Factory
{
    protected $model = KnowledgeTag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'business_id' => Business::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
        ];
    }
}
