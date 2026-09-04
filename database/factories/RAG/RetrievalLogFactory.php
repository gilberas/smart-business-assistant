<?php

namespace Database\Factories\RAG;

use App\Models\Business;
use App\Models\RAG\RetrievalLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetrievalLogFactory extends Factory
{
    protected $model = RetrievalLog::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
            'query' => $this->faker->sentence(),
            'parsed_query' => ['keywords' => $this->faker->words(3), 'has_question' => $this->faker->boolean()],
            'intent' => $this->faker->randomElement(['general', 'pricing', 'product']),
            'primary_strategy' => $this->faker->randomElement(['hybrid', 'keyword', 'fulltext']),
            'total_results' => $this->faker->numberBetween(0, 20),
            'returned_results' => $this->faker->numberBetween(0, 10),
            'execution_time_ms' => $this->faker->randomFloat(2, 5, 500),
            'sources_used' => $this->faker->randomElements(['knowledge_article', 'faq', 'product', 'business_information'], 2),
            'top_chunk_ids' => [],
            'context_token_estimate' => $this->faker->numberBetween(100, 4000),
        ];
    }
}
