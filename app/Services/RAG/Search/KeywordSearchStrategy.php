<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\RAG\KnowledgeChunk;
use App\Models\Product;
use Illuminate\Support\Collection;

class KeywordSearchStrategy implements SearchStrategyInterface
{
    public function name(): string
    {
        return 'keyword';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return strlen(trim($query->query)) >= 2;
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $keywords = $this->extractKeywords($query->query);
        $results = [];
        $sourcesUsed = [];

        if (empty($keywords)) {
            return new SearchResponseDTO(
                query: $query->query,
                strategy: $this->name(),
                executionTimeMs: (microtime(true) - $start) * 1000,
            );
        }

        if ($this->shouldSearch('knowledge_article', $query->sources)) {
            $sourcesUsed[] = 'knowledge_article';
            $articles = $this->searchArticles($query->businessId, $keywords);
            foreach ($articles as $article) {
                $results[] = $this->buildResult($article, 'knowledge_article', $keywords);
            }
        }

        if ($this->shouldSearch('faq', $query->sources)) {
            $sourcesUsed[] = 'faq';
            $faqs = $this->searchFaqs($query->businessId, $keywords);
            foreach ($faqs as $faq) {
                $results[] = $this->buildResult($faq, 'faq', $keywords);
            }
        }

        if ($this->shouldSearch('product', $query->sources)) {
            $sourcesUsed[] = 'product';
            $products = $this->searchProducts($query->businessId, $keywords);
            foreach ($products as $product) {
                $results[] = $this->buildResult($product, 'product', $keywords);
            }
        }

        foreach (['business_information', 'service', 'document', 'conversation', 'customer', 'policy'] as $sourceType) {
            if ($this->shouldSearch($sourceType, $query->sources)) {
                $sourcesUsed[] = $sourceType;
                $chunks = $this->searchChunks($query->businessId, $sourceType, $keywords);
                foreach ($chunks as $chunk) {
                    $results[] = $this->buildChunkResult($chunk, $sourceType, $keywords);
                }
            }
        }

        $results = $this->rankByKeywordMatch($results, $keywords);

        return new SearchResponseDTO(
            query: $query->query,
            strategy: $this->name(),
            results: array_slice($results, 0, $query->maxResults),
            totalResults: count($results),
            executionTimeMs: (microtime(true) - $start) * 1000,
            sourcesUsed: $sourcesUsed,
        );
    }

    private function extractKeywords(string $query): array
    {
        $words = str_word_count(strtolower($query), 1);
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for',
            'of', 'and', 'or', 'but', 'it', 'its', 'this', 'that', 'with', 'from', 'by', 'be',
            'how', 'what', 'when', 'where', 'why', 'which', 'who', 'whom', 'whose', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'can', 'may', 'might', 'shall', 'has',
            'have', 'had', 'not', 'no', 'nor', 'so', 'if', 'about', 'into', 'over', 'after',
            'before', 'between', 'under', 'above', 'below', 'up', 'down', 'out', 'off', 'then',
            'than', 'also', 'very', 'just', 'because', 'as', 'until', 'while', 'please', 'help'];

        return array_values(array_unique(array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords))));
    }

    private function shouldSearch(string $source, array $requestedSources): bool
    {
        if (empty($requestedSources)) {
            return true;
        }
        return in_array($source, $requestedSources);
    }

    private function searchArticles(int $businessId, array $keywords): Collection
    {
        return KnowledgeArticle::where('business_id', $businessId)
            ->where('status', 'published')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%")
                      ->orWhere('content', 'like', "%{$keyword}%")
                      ->orWhere('excerpt', 'like', "%{$keyword}%");
                }
            })
            ->limit(20)
            ->get();
    }

    private function searchFaqs(int $businessId, array $keywords): Collection
    {
        return KnowledgeFaq::where('business_id', $businessId)
            ->where('is_published', true)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('question', 'like', "%{$keyword}%")
                      ->orWhere('answer', 'like', "%{$keyword}%");
                }
            })
            ->limit(20)
            ->get();
    }

    private function searchProducts(int $businessId, array $keywords): Collection
    {
        return Product::where('business_id', $businessId)
            ->where('status', 'published')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                }
            })
            ->limit(20)
            ->get();
    }

    private function searchChunks(int $businessId, string $sourceType, array $keywords): Collection
    {
        return KnowledgeChunk::where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('content', 'like', "%{$keyword}%")
                      ->orWhere('heading', 'like', "%{$keyword}%");
                }
            })
            ->limit(20)
            ->get();
    }

    private function buildResult($model, string $sourceType, array $keywords): SearchResultDTO
    {
        $matchCount = 0;
        $title = '';
        $content = '';
        $category = null;
        $tags = [];

        if ($sourceType === 'knowledge_article') {
            $title = $model->title;
            $content = $model->content ?? $model->excerpt ?? '';
            $category = $model->category?->name;
            $popularity = ($model->view_count ?? 0) + ($model->helpful_count ?? 0) * 2;
            $matchCount = $this->countKeywordMatches($title . ' ' . $content, $keywords);
            return new SearchResultDTO(
                sourceType: $sourceType,
                sourceId: $model->id,
                title: $title,
                content: $content,
                relevanceScore: $matchCount > 0 ? min(1.0, $matchCount / count($keywords)) : 0,
                metadata: ['view_count' => $model->view_count, 'helpful_count' => $model->helpful_count],
                category: $category,
                createdAt: $model->created_at,
                updatedAt: $model->updated_at,
            );
        }

        if ($sourceType === 'faq') {
            $title = $model->question;
            $content = $model->answer ?? '';
            $matchCount = $this->countKeywordMatches($title . ' ' . $content, $keywords);
            return new SearchResultDTO(
                sourceType: $sourceType,
                sourceId: $model->id,
                title: $title,
                content: $content,
                relevanceScore: $matchCount > 0 ? min(1.0, $matchCount / count($keywords)) : 0,
                createdAt: $model->created_at,
                updatedAt: $model->updated_at,
            );
        }

        if ($sourceType === 'product') {
            $title = $model->name;
            $content = $model->description ?? '';
            $category = $model->category?->name;
            $matchCount = $this->countKeywordMatches($title . ' ' . $content, $keywords);
            return new SearchResultDTO(
                sourceType: $sourceType,
                sourceId: $model->id,
                title: $title,
                content: $content,
                relevanceScore: $matchCount > 0 ? min(1.0, $matchCount / count($keywords)) : 0,
                metadata: ['price' => $model->price, 'currency' => $model->currency],
                category: $category,
                createdAt: $model->created_at,
                updatedAt: $model->updated_at,
            );
        }

        return new SearchResultDTO(sourceType: $sourceType, sourceId: $model->id ?? 0);
    }

    private function buildChunkResult(KnowledgeChunk $chunk, string $sourceType, array $keywords): SearchResultDTO
    {
        $text = trim(($chunk->heading ? $chunk->heading . ' ' : '') . $chunk->content);
        $matchCount = $this->countKeywordMatches($text, $keywords);
        $metadata = is_array($chunk->metadata) ? $chunk->metadata : [];

        return new SearchResultDTO(
            sourceType: $sourceType,
            sourceId: (int) $chunk->source_id,
            chunkId: $chunk->id,
            chunkUuid: $chunk->uuid,
            title: $chunk->heading ?: ucfirst(str_replace('_', ' ', $sourceType)),
            content: $chunk->content,
            heading: $chunk->heading,
            relevanceScore: count($keywords) > 0 ? min(1.0, $matchCount / count($keywords)) : 0,
            metadata: $metadata,
            category: $metadata['category'] ?? null,
            tags: $metadata['tags'] ?? [],
            createdAt: $chunk->created_at,
            updatedAt: $chunk->updated_at,
        );
    }

    private function countKeywordMatches(string $text, array $keywords): int
    {
        $text = strtolower($text);
        $count = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $count++;
            }
        }
        return $count;
    }

    private function rankByKeywordMatch(array $results, array $keywords): array
    {
        usort($results, fn(SearchResultDTO $a, SearchResultDTO $b) => $b->relevanceScore <=> $a->relevanceScore);
        return $results;
    }
}
