<?php

namespace App\DTOs\RAG;

readonly class RankingScoreDTO
{
    public function __construct(
        public float $keywordRelevance = 0.0,
        public float $categoryRelevance = 0.0,
        public float $tagRelevance = 0.0,
        public float $popularity = 0.0,
        public float $recency = 0.0,
        public float $businessPriority = 0.0,
        public float $vectorSimilarity = 0.0,
    ) {}

    public function total(array $weights = []): float
    {
        $weights = $weights + config('rag.ranking.weights', [
            'keyword_relevance' => 0.30,
            'category_relevance' => 0.15,
            'tag_relevance' => 0.10,
            'popularity' => 0.10,
            'recency' => 0.15,
            'business_priority' => 0.20,
        ]);

        return
            ($this->keywordRelevance * $weights['keyword_relevance']) +
            ($this->categoryRelevance * $weights['category_relevance']) +
            ($this->tagRelevance * $weights['tag_relevance']) +
            ($this->popularity * $weights['popularity']) +
            ($this->recency * $weights['recency']) +
            ($this->businessPriority * $weights['business_priority']);
    }

    public function toArray(): array
    {
        return [
            'keyword_relevance' => round($this->keywordRelevance, 4),
            'category_relevance' => round($this->categoryRelevance, 4),
            'tag_relevance' => round($this->tagRelevance, 4),
            'popularity' => round($this->popularity, 4),
            'recency' => round($this->recency, 4),
            'business_priority' => round($this->businessPriority, 4),
            'vector_similarity' => round($this->vectorSimilarity, 4),
        ];
    }
}
