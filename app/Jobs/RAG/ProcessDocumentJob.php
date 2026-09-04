<?php

namespace App\Jobs\RAG;

use App\Enums\RAG\IngestionStatus;
use App\Models\RAG\DocumentIngestion;
use App\Services\RAG\DocumentProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly DocumentIngestion $ingestion,
        public readonly array $options = [],
    ) {}

    public function handle(DocumentProcessingService $processor): void
    {
        try {
            $processor->reprocess($this->ingestion, $this->options);
        } catch (\Throwable $e) {
            $this->ingestion->update([
                'status' => IngestionStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
