<?php

namespace App\DTOs\RAG;

readonly class ChunkDTO
{
    public function __construct(
        public int $chunkIndex,
        public string $content,
        public ?string $heading = null,
        public string $strategy = 'paragraph',
        public array $metadata = [],
        public int $wordCount = 0,
        public int $charCount = 0,
        public int $tokenEstimate = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'chunk_index' => $this->chunkIndex,
            'content' => $this->content,
            'heading' => $this->heading,
            'strategy' => $this->strategy,
            'metadata' => $this->metadata,
            'word_count' => $this->wordCount,
            'char_count' => $this->charCount,
            'token_estimate' => $this->tokenEstimate,
        ];
    }
}
