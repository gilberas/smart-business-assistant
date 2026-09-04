<?php

namespace App\Events\RAG;

use App\Models\RAG\DocumentIngestion;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentIngestionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly DocumentIngestion $ingestion,
        public readonly string $errorMessage,
    ) {}
}
