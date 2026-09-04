<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\KnowledgeCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeCollectionFactory extends Factory
{
    protected $model = KnowledgeCollection::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'business_id' => Business::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'cover_image' => null,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
