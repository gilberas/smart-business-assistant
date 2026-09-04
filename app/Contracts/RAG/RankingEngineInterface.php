<?php

namespace App\Contracts\RAG;

use App\DTOs\RAG\RankingScoreDTO;
use App\DTOs\RAG\SearchResultDTO;

interface RankingEngineInterface
{
    public function rank(array $results, string $query, array $options = []): array;
    public function score(SearchResultDTO $result, string $query, array $options = []): RankingScoreDTO;
    public function name(): string;
}
