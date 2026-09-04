<?php

namespace App\DTOs\RAG;

readonly class SearchResponseDTO
{
    public function __construct(
        public string $query,
        public string $intent = 'general',
        public string $strategy = 'hybrid',
        public array $results = [],
        public int $totalResults = 0,
        public float $executionTimeMs = 0.0,
        public array $sourcesUsed = [],
        public array $errors = [],
    ) {}

    public function topResults(int $count = 5): array
    {
        return array_slice($this->results, 0, $count);
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'intent' => $this->intent,
            'strategy' => $this->strategy,
            'total_results' => $this->totalResults,
            'execution_time_ms' => round($this->executionTimeMs, 2),
            'sources_used' => $this->sourcesUsed,
            'results' => array_map(fn(SearchResultDTO $r) => $r->toArray(), $this->results),
            'errors' => $this->errors,
        ];
    }
}
