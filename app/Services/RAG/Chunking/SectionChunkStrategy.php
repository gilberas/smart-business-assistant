<?php

namespace App\Services\RAG\Chunking;

use App\Contracts\RAG\ChunkingStrategyInterface;
use App\DTOs\RAG\ChunkDTO;

class SectionChunkStrategy implements ChunkingStrategyInterface
{
    public function name(): string
    {
        return 'section';
    }

    public function supports(string $content, array $options = []): bool
    {
        return preg_match('/^#{1,6}\s/m', $content) === 1 || preg_match('/^[A-Z][^.]*?:\s/m', $content) === 1;
    }

    public function chunk(string $content, array $options = []): array
    {
        $maxSize = $options['max_size'] ?? config('rag.chunking.section.max_size', 2048);
        $sections = $this->splitByHeadings($content);
        $chunks = [];

        foreach ($sections as $section) {
            if (str_word_count($section['content']) > $maxSize) {
                $subChunks = $this->splitLargeSection($section['content'], $maxSize);
                foreach ($subChunks as $sub) {
                    $chunks[] = $this->buildChunk($sub, count($chunks), $section['heading']);
                }
            } else {
                $chunks[] = $this->buildChunk($section['content'], count($chunks), $section['heading']);
            }
        }

        if (empty($chunks) && strlen(trim($content)) > 0) {
            $chunks[] = $this->buildChunk($content, 0, null);
        }

        return $chunks;
    }

    private function splitByHeadings(string $content): array
    {
        $lines = explode("\n", $content);
        $sections = [];
        $currentHeading = null;
        $currentContent = '';

        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches) || preg_match('/^([A-Z][^.]*?):\s*$/', $line, $matches)) {
                if (!empty(trim($currentContent))) {
                    $sections[] = ['heading' => $currentHeading, 'content' => trim($currentContent)];
                }
                $currentHeading = $matches[2] ?? $matches[1];
                $currentContent = '';
            } else {
                $currentContent .= $line . "\n";
            }
        }

        if (!empty(trim($currentContent))) {
            $sections[] = ['heading' => $currentHeading, 'content' => trim($currentContent)];
        }

        return $sections;
    }

    private function splitLargeSection(string $content, int $maxSize): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;

            if (str_word_count($current . "\n\n" . $p) > $maxSize && !empty($current)) {
                $chunks[] = $current;
                $current = $p;
            } else {
                $current .= ($current ? "\n\n" : '') . $p;
            }
        }

        if (!empty(trim($current))) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function buildChunk(string $content, int $index, ?string $heading): ChunkDTO
    {
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
