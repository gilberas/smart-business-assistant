<?php

namespace App\Services\RAG;

use App\Enums\RAG\ChunkStrategyType;
use App\Services\RAG\Chunking\ChunkingStrategyFactory;

class ChunkingService
{
    public function __construct(
        private readonly ChunkingStrategyFactory $strategyFactory,
    ) {}

    public function chunk(string $content, ?string $strategy = null, array $options = []): array
    {
        $chunker = $this->strategyFactory->forContent($content, $strategy);
        return $chunker->chunk($content, $options);
    }

    public function chunkWithStrategy(string $content, string $strategy, array $options = []): array
    {
        $chunker = $this->strategyFactory->make($strategy);
        return $chunker->chunk($content, $options);
    }

    public function estimateChunks(string $content, ?string $strategy = null): int
    {
        return count($this->chunk($content, $strategy));
    }

    public function availableStrategies(): array
    {
        return $this->strategyFactory->all();
    }

    public function defaultStrategy(): string
    {
        return config('rag.chunking.default_strategy', 'paragraph');
    }
}
