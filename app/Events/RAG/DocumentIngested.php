<?php

namespace App\Events\RAG;

use App\Models\RAG\DocumentIngestion;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentIngested
{
    use Dispatchable;

    public function __construct(
        public readonly DocumentIngestion $ingestion,
        public readonly int $chunkCount,
    ) {}
}
