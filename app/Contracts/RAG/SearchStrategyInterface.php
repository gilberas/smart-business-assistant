<?php

namespace App\Contracts\RAG;

use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;

interface SearchStrategyInterface
{
    public function search(SearchQueryDTO $query): SearchResponseDTO;
    public function name(): string;
    public function supports(SearchQueryDTO $query): bool;
}
