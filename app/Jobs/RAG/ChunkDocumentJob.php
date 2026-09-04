<?php

namespace App\Jobs\RAG;

use App\Services\RAG\ChunkingService;
use App\Models\RAG\KnowledgeChunk;
use App\Repositories\RAG\KnowledgeChunkRepository;
use App\Repositories\RAG\DocumentIngestionRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ChunkDocumentJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $businessId,
        public readonly string $ingestibleType,
        public readonly int $ingestibleId,
        public readonly string $sourceType,
        public readonly string $content,
        public readonly string $strategy = 'paragraph',
        public readonly array $options = [],
    ) {}

    public function handle(
        ChunkingService $chunkingService,
        KnowledgeChunkRepository $chunkRepository,
    ): void {
        $chunks = $chunkingService->chunk($this->content, $this->strategy, $this->options);

        $existingChunks = KnowledgeChunk::where('business_id', $this->businessId)
            ->where('chunkable_type', $this->ingestibleType)
            ->where('chunkable_id', $this->ingestibleId)
            ->get();

        foreach ($existingChunks as $existing) {
            $existing->delete();
        }

        foreach ($chunks as $index => $chunkDTO) {
            $chunkRepository->create([
                'business_id' => $this->businessId,
                'uuid' => (string) Str::uuid(),
                'chunkable_type' => $this->ingestibleType,
                'chunkable_id' => $this->ingestibleId,
                'source_type' => $this->sourceType,
                'source_id' => $this->ingestibleId,
                'chunk_index' => $index,
                'strategy' => $this->strategy,
                'content' => $chunkDTO->content,
                'heading' => $chunkDTO->heading,
                'metadata' => $chunkDTO->metadata,
                'word_count' => $chunkDTO->wordCount,
                'char_count' => $chunkDTO->charCount,
                'token_estimate' => $chunkDTO->tokenEstimate,
                'is_active' => true,
            ]);
        }
    }
}
