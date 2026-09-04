<?php

namespace App\Services\RAG\Search;

use App\Contracts\RAG\SearchStrategyInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\Product;
use App\Models\BusinessInformation;
use Illuminate\Support\Collection;

class CategorySearchStrategy implements SearchStrategyInterface
{
    public function name(): string
    {
        return 'category';
    }

    public function supports(SearchQueryDTO $query): bool
    {
        return !empty($query->categories) || $this->hasCategoryTerms($query->query);
    }

    public function search(SearchQueryDTO $query): SearchResponseDTO
    {
        $start = microtime(true);
        $results = [];
        $sourcesUsed = [];
        $categories = !empty($query->categories) ? $query->categories : $this->inferCategories($query->query);

        if (empty($categories)) {
            return new SearchResponseDTO(
                query: $query->query,
                strategy: $this->name(),
                executionTimeMs: (microtime(true) - $start) * 1000,
            );
        }

        if ($this->shouldSearch('knowledge_article', $query->sources)) {
            $sourcesUsed[] = 'knowledge_article';
            $articles = $this->searchArticlesByCategory($query->businessId, $categories);
            foreach ($articles as $article) {
                $results[] = $this->buildArticleResult($article, $categories);
            }
        }

        if ($this->shouldSearch('faq', $query->sources)) {
            $sourcesUsed[] = 'faq';
            $faqs = $this->searchFaqsByCategory($query->businessId, $categories);
            foreach ($faqs as $faq) {
                $results[] = $this->buildFaqResult($faq, $categories);
            }
        }

        if ($this->shouldSearch('product', $query->sources)) {
            $sourcesUsed[] = 'product';
            $products = $this->searchProductsByCategory($query->businessId, $categories);
            foreach ($products as $product) {
                $results[] = $this->buildProductResult($product, $categories);
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

    private function hasCategoryTerms(string $query): bool
    {
        return !empty($this->inferCategories($query));
    }

    private function inferCategories(string $query): array
    {
        $query = strtolower($query);
        $categoryMap = [
            'pricing' => ['pricing', 'price', 'cost', 'fee', 'subscription', 'plan', 'billing', 'payment'],
            'support' => ['support', 'help', 'contact', 'assist', 'issue', 'problem', 'bug', 'error'],
            'product' => ['product', 'item', 'merchandise', 'inventory', 'stock', 'variant'],
            'shipping' => ['shipping', 'delivery', 'ship', 'tracking', 'fulfillment', 'dispatch'],
            'returns' => ['return', 'refund', 'exchange', 'cancel', 'cancellation', 'policy'],
            'faq' => ['faq', 'question', 'common', 'frequently'],
            'guide' => ['guide', 'tutorial', 'how to', 'walkthrough', 'instruction', 'manual'],
            'policy' => ['policy', 'terms', 'condition', 'privacy', 'legal', 'compliance'],
            'business_info' => ['hours', 'location', 'address', 'contact', 'about', 'holiday'],
        ];

        $matched = [];
        foreach ($categoryMap as $category => $terms) {
            foreach ($terms as $term) {
                if (str_contains($query, $term)) {
                    $matched[] = $category;
                    break;
                }
            }
        }

        return $matched;
    }

    private function shouldSearch(string $source, array $requestedSources): bool
    {
        if (empty($requestedSources)) {
            return true;
        }
        return in_array($source, $requestedSources);
    }

    private function searchArticlesByCategory(int $businessId, array $categories): Collection
    {
        return KnowledgeArticle::where('business_id', $businessId)
            ->where('status', 'published')
            ->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('slug', $categories)
                  ->orWhereIn('name', $categories);
            })
            ->with('category')
            ->limit(20)
            ->get();
    }

    private function searchFaqsByCategory(int $businessId, array $categories): Collection
    {
        return KnowledgeFaq::where('business_id', $businessId)
            ->where('is_published', true)
            ->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('slug', $categories)
                  ->orWhereIn('name', $categories);
            })
            ->with('category')
            ->limit(20)
            ->get();
    }

    private function searchProductsByCategory(int $businessId, array $categories): Collection
    {
        return Product::where('business_id', $businessId)
            ->where('status', 'published')
            ->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('slug', $categories)
                  ->orWhereIn('name', $categories);
            })
            ->with('category')
            ->limit(20)
            ->get();
    }

    private function buildArticleResult($article, array $categories): SearchResultDTO
    {
        return new SearchResultDTO(
            sourceType: 'knowledge_article',
            sourceId: $article->id,
            title: $article->title,
            content: $article->excerpt ?? $article->content ?? '',
            relevanceScore: 0.8,
            category: $article->category?->name,
            createdAt: $article->created_at,
            updatedAt: $article->updated_at,
        );
    }

    private function buildFaqResult($faq, array $categories): SearchResultDTO
    {
        return new SearchResultDTO(
            sourceType: 'faq',
            sourceId: $faq->id,
            title: $faq->question,
            content: $faq->answer ?? '',
            relevanceScore: 0.8,
            category: $faq->category?->name,
            createdAt: $faq->created_at,
            updatedAt: $faq->updated_at,
        );
    }

    private function buildProductResult($product, array $categories): SearchResultDTO
    {
        return new SearchResultDTO(
            sourceType: 'product',
            sourceId: $product->id,
            title: $product->name,
            content: $product->description ?? '',
            relevanceScore: 0.8,
            category: $product->category?->name,
            metadata: ['price' => $product->price, 'currency' => $product->currency],
            createdAt: $product->created_at,
            updatedAt: $product->updated_at,
        );
    }
}
