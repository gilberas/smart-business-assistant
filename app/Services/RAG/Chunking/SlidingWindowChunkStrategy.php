<?php

namespace App\Services\RAG\Chunking;

use App\Contracts\RAG\ChunkingStrategyInterface;
use App\DTOs\RAG\ChunkDTO;

class SlidingWindowChunkStrategy implements ChunkingStrategyInterface
{
    public function name(): string
    {
        return 'sliding_window';
    }

    public function supports(string $content, array $options = []): bool
    {
        return strlen(trim($content)) > 0;
    }

    public function chunk(string $content, array $options = []): array
    {
        $windowSize = $options['size'] ?? config('rag.chunking.sliding_window.size', 512);
        $overlap = $options['overlap'] ?? config('rag.chunking.sliding_window.overlap', 128);
        $words = str_word_count($content, 1);
        $chunks = [];

        if (count($words) <= $windowSize) {
            return [new ChunkDTO(
                chunkIndex: 0,
                content: $content,
                strategy: $this->name(),
                wordCount: count($words),
                charCount: strlen($content),
                tokenEstimate: (int) ceil(strlen($content) / 4),
            )];
        }

        $start = 0;
        $index = 0;

        while ($start < count($words)) {
            $end = min($start + $windowSize, count($words));
            $chunkWords = array_slice($words, $start, $windowSize);
            $chunkContent = implode(' ', $chunkWords);

            $chunks[] = new ChunkDTO(
                chunkIndex: $index,
                content: $chunkContent,
                strategy: $this->name(),
                wordCount: count($chunkWords),
                charCount: strlen($chunkContent),
                tokenEstimate: (int) ceil(strlen($chunkContent) / 4),
            );

            if ($end >= count($words)) break;

            $start += ($windowSize - $overlap);
            $index++;
        }

        return $chunks;
    }
}
