<?php

namespace App\Services\RAG\Chunking;

use App\Contracts\RAG\ChunkingStrategyInterface;

class ChunkingStrategyFactory
{
    public function __construct(
        private readonly ParagraphChunkStrategy $paragraphChunk,
        private readonly SectionChunkStrategy $sectionChunk,
        private readonly SlidingWindowChunkStrategy $slidingWindowChunk,
        private readonly FixedSizeChunkStrategy $fixedSizeChunk,
    ) {}

    public function make(?string $strategy = null): ChunkingStrategyInterface
    {
        return match ($strategy) {
            'paragraph', null => $this->paragraphChunk,
            'section' => $this->sectionChunk,
            'sliding_window' => $this->slidingWindowChunk,
            'fixed_size' => $this->fixedSizeChunk,
            default => throw new \InvalidArgumentException("Unknown chunking strategy: {$strategy}"),
        };
    }

    public function forContent(string $content, ?string $preferred = null): ChunkingStrategyInterface
    {
        if ($preferred !== null) {
            return $this->make($preferred);
        }

        $strategies = [
            $this->sectionChunk,
            $this->paragraphChunk,
            $this->slidingWindowChunk,
            $this->fixedSizeChunk,
        ];

        foreach ($strategies as $strategy) {
            if ($strategy->supports($content)) {
                return $strategy;
            }
        }

        return $this->paragraphChunk;
    }

    public function all(): array
    {
        return [
            'paragraph' => $this->paragraphChunk,
            'section' => $this->sectionChunk,
            'sliding_window' => $this->slidingWindowChunk,
            'fixed_size' => $this->fixedSizeChunk,
        ];
    }
}
