<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\KnowledgeArticle;
use App\Models\Product;
use Illuminate\Support\Collection;

class TagSearchStrategy implements SearchStrategyInterface
{
    public function name(): string
    {
        return 'tag';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return !empty($query->tags);
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $results = [];
        $sourcesUsed = [];

        if (empty($query->tags)) {
            return new SearchResponseDTO(
                query: $query->query,
                strategy: $this->name(),
                executionTimeMs: (microtime(true) - $start) * 1000,
            );
        }

        if ($this->shouldSearch('knowledge_article', $query->sources)) {
            $sourcesUsed[] = 'knowledge_article';
            $articles = $this->searchArticlesByTags($query->businessId, $query->tags);
            foreach ($articles as $article) {
                $results[] = $this->buildResult($article, 'knowledge_article', $query->tags);
            }
        }

        if ($this->shouldSearch('product', $query->sources)) {
            $sourcesUsed[] = 'product';
            $products = $this->searchProductsByTags($query->businessId, $query->tags);
            foreach ($products as $product) {
                $results[] = $this->buildResult($product, 'product', $query->tags);
            }
        }

        return new SearchResponseDTO(
            query: $query->query,
            strategy: $this->name(),
            results: array_slice($results, 0, $query->maxResults),
            totalResults: count($results),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: $sourcesUsed,
        );
    }

    private function shouldSearch(string $source, array $requestedSources): bool
    {
        if (empty($requestedSources)) {
            return true;
        }
        return in_array($source, $requestedSources);
    }

    private function searchArticlesByTags(int $businessId, array $tags): Collection
    {
        return KnowledgeArticle::where('business_id', $businessId)
            ->where('status', 'published')
            ->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            })
            ->with('tags')
            ->limit(20)
            ->get();
    }

    private function searchProductsByTags(int $businessId, array $tags): Collection
    {
        return Product::where('business_id', $businessId)
            ->where('status', 'published')
            ->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhere('metadata', 'like', "%{$tag}%");
                }
            })
            ->limit(20)
            ->get();
    }

    private function buildResult($model, string $sourceType, array $tags): SearchResultDTO
    {
        $matchedTags = 0;
        $title = '';
        $content = '';

        if ($sourceType === 'knowledge_article') {
            $title = $model->title;
            $content = $model->excerpt ?? $model->content ?? '';
            $modelTags = $model->tags?->pluck('name')->toArray() ?? [];
            $matchedTags = count(array_intersect($tags, $modelTags));
            return new SearchResultDTO(
                sourceType: $sourceType,
                sourceId: $model->id,
                title: $title,
                content: $content,
                relevanceScore: count($tags) > 0 ? $matchedTags / count($tags) : 0,
                tags: $modelTags,
                category: $model->category?->name,
                createdAt: $model->created_at,
                updatedAt: $model->updated_at,
            );
        }

        return new SearchResultDTO(
            sourceType: $sourceType,
            sourceId: $model->id ?? 0,
            title: $model->name ?? '',
            relevanceScore: 0,
        );
    }
}
