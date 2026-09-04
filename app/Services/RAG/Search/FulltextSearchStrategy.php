<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\RAG\KnowledgeChunk;
use Illuminate\Support\Facades\DB;

class FulltextSearchStrategy implements SearchStrategyInterface
{
    public function name(): string
    {
        return 'fulltext';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return strlen(trim($query->query)) >= 3;
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $results = [];

        $chunks = KnowledgeChunk::select(
            'knowledge_chunks.*',
            DB::raw("MATCH(content) AGAINST(? IN BOOLEAN MODE) as relevance"),
            DB::raw("SUBSTRING(content, 1, 300) as content_preview"),
        )
        ->addBinding($this->buildBooleanQuery($query->query), 'select')
        ->where('business_id', $query->businessId)
        ->where('is_active', true)
        ->havingRaw('relevance > ?', [$query->minScore])
        ->orderByRaw('relevance DESC')
        ->limit($query->maxResults)
        ->get();

        foreach ($chunks as $chunk) {
            $results[] = new SearchResultDTO(
                sourceType: $chunk->source_type,
                sourceId: $chunk->source_id,
                chunkId: $chunk->id,
                chunkUuid: $chunk->uuid,
                title: $chunk->heading ?? $chunk->source_type,
                content: $chunk->content_preview ?? substr($chunk->content, 0, 300),
                heading: $chunk->heading,
                relevanceScore: min(1.0, (float) ($chunk->relevance ?? 0) / 5),
                metadata: $chunk->metadata ?? [],
                createdAt: $chunk->created_at,
                updatedAt: $chunk->updated_at,
            );
        }

        return new SearchResponseDTO(
            query: $query->query,
            strategy: $this->name(),
            results: $results,
            totalResults: count($results),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: ['knowledge_chunks'],
        );
    }

    private function buildBooleanQuery(string $query): string
    {
        $words = str_word_count($query, 1);
        $terms = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3) {
                $terms[] = '+' . $word . '*';
            }
        }
        return implode(' ', $terms);
    }
}
