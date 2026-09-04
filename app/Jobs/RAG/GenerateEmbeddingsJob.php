<?php

namespace App\Jobs\RAG;

use App\Contracts\RAG\VectorDatabaseInterface;
use App\Models\RAG\KnowledgeChunk;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $chunkId,
        public readonly string $vectorDb = 'pinecone',
    ) {}

    public function handle(VectorDatabaseInterface $vectorDb): void
    {
        $chunk = KnowledgeChunk::find($this->chunkId);
        if (!$chunk || !$chunk->is_active) {
            return;
        }

        // Future: Call embedding model to generate vector
        // Current: Placeholder for when vector DB integration is ready
        logger()->info('Embedding generation placeholder', [
            'chunk_id' => $this->chunkId,
            'vector_db' => $this->vectorDb,
            'content_length' => strlen($chunk->content),
        ]);
    }
}
