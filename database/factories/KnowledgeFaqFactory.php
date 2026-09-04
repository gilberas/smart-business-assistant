<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeFaqFactory extends Factory
{
    protected $model = KnowledgeFaq::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'article_id' => null,
            'category_id' => null,
            'question' => fake()->unique()->sentence() . '?',
            'answer' => fake()->paragraphs(2, true),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => false,
        ]);
    }
}
