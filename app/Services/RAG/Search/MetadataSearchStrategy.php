<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\BusinessInformation;
use App\Models\RAG\KnowledgeChunk;
use App\Models\RAG\ChunkMetadata;
use Illuminate\Support\Collection;

class MetadataSearchStrategy implements SearchStrategyInterface
{
    public function name(): string
    {
        return 'metadata';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return !empty($query->metadata ?? []) || $this->hasMetadataTerms($query->query);
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $results = [];

        $businessInfo = BusinessInformation::where('business_id', $query->businessId)
            ->where(function ($q) use ($query) {
                $q->where('key', 'like', "%{$query->query}%")
                  ->orWhere('value', 'like', "%{$query->query}%")
                  ->orWhere('type', 'like', "%{$query->query}%");
            })
            ->limit($query->maxResults)
            ->get();

        foreach ($businessInfo as $info) {
            $results[] = new SearchResultDTO(
                sourceType: 'business_information',
                sourceId: $info->id,
                title: $info->key,
                content: $info->value ?? '',
                relevanceScore: 0.9,
                metadata: ['type' => $info->type, 'is_public' => $info->is_public],
                createdAt: $info->created_at,
                updatedAt: $info->updated_at,
            );
        }

        return new SearchResponseDTO(
            query: $query->query,
            strategy: $this->name(),
            results: array_slice($results, 0, $query->maxResults),
            totalResults: count($results),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: ['business_information'],
        );
    }

    private function hasMetadataTerms(string $query): bool
    {
        $metadataTerms = ['hours', 'location', 'address', 'phone', 'email', 'policy',
            'shipping', 'return', 'warranty', 'payment', 'about', 'holiday'];
        $query = strtolower($query);
        foreach ($metadataTerms as $term) {
            if (str_contains($query, $term)) {
                return true;
            }
        }
        return false;
    }
}
