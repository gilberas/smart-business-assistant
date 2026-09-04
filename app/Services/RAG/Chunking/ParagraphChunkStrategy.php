<?php

namespace App\Services\RAG\Chunking;

use App\Contracts\RAG\ChunkingStrategyInterface;
use App\DTOs\RAG\ChunkDTO;

class ParagraphChunkStrategy implements ChunkingStrategyInterface
{
    public function name(): string
    {
        return 'paragraph';
    }

    public function supports(string $content, array $options = []): bool
    {
        return strlen(trim($content)) > 0;
    }

    public function chunk(string $content, array $options = []): array
    {
        $maxSize = $options['max_size'] ?? config('rag.chunking.paragraph.max_size', 1024);
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $paragraphs = array_map('trim', $paragraphs);
        $paragraphs = array_filter($paragraphs, fn($p) => strlen($p) > 0);
        $paragraphs = array_values($paragraphs);

        $chunks = [];
        $currentChunk = '';
        $currentWords = 0;

        foreach ($paragraphs as $index => $paragraph) {
            $wordCount = str_word_count($paragraph);
            $charCount = strlen($paragraph);

            if ($currentWords + $wordCount > $maxSize && !empty($currentChunk)) {
                $chunks[] = $this->buildChunk($currentChunk, count($chunks));
                $currentChunk = '';
                $currentWords = 0;
            }

            $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            $currentWords += $wordCount;
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = $this->buildChunk($currentChunk, count($chunks));
        }

        return $chunks;
    }

    private function buildChunk(string $content, int $index): ChunkDTO
    {
        $firstLine = explode("\n", trim($content))[0];
        $heading = strlen($firstLine) < 200 ? $firstLine : null;

        return new ChunkDTO(
            chunkIndex: $index,
            content: $content,
            heading: $heading,
            strategy: $this->name(),
            wordCount: str_word_count($content),
            charCount: strlen($content),
            tokenEstimate: (int) ceil(strlen($content) / 4),
        );
    }
}
