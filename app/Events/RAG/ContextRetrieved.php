<?php

namespace App\Events\RAG;

use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\SearchQueryDTO;
use Illuminate\Foundation\Events\Dispatchable;

class ContextRetrieved
{
    use Dispatchable;

    public function __construct(
        public readonly SearchQueryDTO $query,
        public readonly ContextPackageDTO $context,
    ) {}
}
