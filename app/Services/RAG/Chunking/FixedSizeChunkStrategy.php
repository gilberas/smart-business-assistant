<?php

namespace App\Services\RAG\Chunking;

use App\Contracts\RAG\ChunkingStrategyInterface;
use App\DTOs\RAG\ChunkDTO;

class FixedSizeChunkStrategy implements ChunkingStrategyInterface
{
    public function name(): string
    {
        return 'fixed_size';
    }

    public function supports(string $content, array $options = []): bool
    {
        return strlen(trim($content)) > 0;
    }

    public function chunk(string $content, array $options = []): array
    {
        $chunkSize = $options['size'] ?? config('rag.chunking.fixed_size.size', 512);
        $overlap = $options['overlap'] ?? config('rag.chunking.fixed_size.overlap', 64);
        $words = str_word_count($content, 1);
        $chunks = [];

        if (empty($words)) {
            return [];
        }

        if (count($words) <= $chunkSize) {
            $text = implode(' ', $words);
            return [new ChunkDTO(
                chunkIndex: 0,
                content: $text,
                strategy: $this->name(),
                wordCount: count($words),
                charCount: strlen($text),
                tokenEstimate: (int) ceil(strlen($text) / 4),
            )];
        }

        $index = 0;
        $position = 0;

        while ($position < count($words)) {
            $end = min($position + $chunkSize, count($words));
            $chunkWords = array_slice($words, $position, $chunkSize);
            $text = implode(' ', $chunkWords);

            $chunks[] = new ChunkDTO(
                chunkIndex: $index,
                content: $text,
                strategy: $this->name(),
                wordCount: count($chunkWords),
                charCount: strlen($text),
                tokenEstimate: (int) ceil(strlen($text) / 4),
            );

            $position += $chunkSize - $overlap;
            $index++;
        }

        return $chunks;
    }
}
