<?php

namespace App\Contracts\RAG;

use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\ContextPackageDTO;

interface ContextBuilderInterface
{
    public function build(SearchQueryDTO $query, array $rankedResults): ContextPackageDTO;
    public function name(): string;
}
