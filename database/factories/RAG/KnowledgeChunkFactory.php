<?php

namespace Database\Factories\RAG;

use App\Models\Business;
use App\Models\RAG\KnowledgeChunk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeChunkFactory extends Factory
{
    protected $model = KnowledgeChunk::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'uuid' => (string) Str::uuid(),
            'chunkable_type' => 'App\Models\KnowledgeArticle',
            'chunkable_id' => 1,
            'source_type' => 'knowledge_article',
            'source_id' => 1,
            'chunk_index' => 0,
            'strategy' => 'paragraph',
            'content' => $this->faker->paragraphs(3, true),
            'heading' => $this->faker->sentence,
            'metadata' => ['source' => 'factory'],
            'word_count' => $this->faker->numberBetween(50, 500),
            'char_count' => $this->faker->numberBetween(200, 3000),
            'token_estimate' => $this->faker->numberBetween(50, 750),
            'is_active' => true,
        ];
    }
}
