<?php

namespace App\Contracts\RAG;

use App\Models\RAG\DocumentIngestion;

interface DocumentProcessorInterface
{
    public function process(object $ingestible, array $options = []): DocumentIngestion;
    public function reprocess(DocumentIngestion $ingestion, array $options = []): DocumentIngestion;
    public function name(): string;
}
