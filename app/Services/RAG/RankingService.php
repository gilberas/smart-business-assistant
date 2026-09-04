<?php

namespace App\Services\RAG;

use App\DTOs\RAG\RankingScoreDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Contracts\RAG\RankingEngineInterface;

class RankingService implements RankingEngineInterface
{
    public function name(): string
    {
        return 'standard';
    }

    public function rank(array $results, string $query, array $options = []): array
    {
        $weights = $options['weights'] ?? config('rag.ranking.weights', []);

        $scored = array_map(function (SearchResultDTO $result) use ($query, $options, $weights) {
            $score = $this->score($result, $query, $options);
            $totalScore = $score->total($weights);
            return [
                'result' => $result,
                'score' => $score,
                'total' => $totalScore,
            ];
        }, $results);

        usort($scored, fn($a, $b) => $b['total'] <=> $a['total']);

        return array_map(function ($item) {
            $result = $item['result'];
            return new SearchResultDTO(
                sourceType: $result->sourceType,
                sourceId: $result->sourceId,
                chunkId: $result->chunkId,
                chunkUuid: $result->chunkUuid,
                title: $result->title,
                content: $result->content,
                heading: $result->heading,
                url: $result->url,
                relevanceScore: $item['total'],
                rankingBreakdown: $item['score']->toArray(),
                metadata: $result->metadata,
                category: $result->category,
                tags: $result->tags,
                createdAt: $result->createdAt,
                updatedAt: $result->updatedAt,
            );
        }, $scored);
    }

    public function score(SearchResultDTO $result, string $query, array $options = []): RankingScoreDTO
    {
        return new RankingScoreDTO(
            keywordRelevance: $this->scoreKeywordRelevance($result, $query),
            categoryRelevance: $this->scoreCategoryRelevance($result, $query),
            tagRelevance: $this->scoreTagRelevance($result),
            popularity: $this->scorePopularity($result),
            recency: $this->scoreRecency($result),
            businessPriority: $this->scoreBusinessPriority($result),
        );
    }

    private function scoreKeywordRelevance(SearchResultDTO $result, string $query): float
    {
        $queryWords = $this->extractWords($query);
        if (empty($queryWords)) {
            return 0;
        }

        $text = mb_strtolower($result->title . ' ' . $result->content . ' ' . ($result->heading ?? ''));
        $matches = 0;

        foreach ($queryWords as $word) {
            if (str_contains($text, $word)) {
                $matches++;
            }
        }

        $title = mb_strtolower($result->title);
        $titleBonus = 0;
        foreach ($queryWords as $word) {
            if (str_contains($title, $word)) {
                $titleBonus += 0.1;
            }
        }

        return min(1.0, ($matches / max(1, count($queryWords))) + $titleBonus);
    }

    private function scoreCategoryRelevance(SearchResultDTO $result, string $query): float
    {
        if (empty($result->category)) {
            return 0;
        }

        $queryWords = $this->extractWords($query);
        $category = mb_strtolower($result->category);

        foreach ($queryWords as $word) {
            if (str_contains($category, $word) || str_contains($word, $category)) {
                return 1.0;
            }
        }

        return 0;
    }

    private function scoreTagRelevance(SearchResultDTO $result): float
    {
        if (empty($result->tags)) {
            return 0;
        }
        return min(1.0, count($result->tags) / 5);
    }

    private function scorePopularity(SearchResultDTO $result): float
    {
        $viewCount = $result->metadata['view_count'] ?? 0;
        $helpfulCount = $result->metadata['helpful_count'] ?? 0;
        $score = ($viewCount * 0.01) + ($helpfulCount * 0.05);
        return min(1.0, $score);
    }

    private function scoreRecency(SearchResultDTO $result): float
    {
        if ($result->updatedAt === null) {
            return 0.5;
        }

        $daysSinceUpdate = (int) $result->updatedAt->diffInDays(now());

        if ($daysSinceUpdate <= 7) return 1.0;
        if ($daysSinceUpdate <= 30) return 0.8;
        if ($daysSinceUpdate <= 90) return 0.6;
        if ($daysSinceUpdate <= 180) return 0.4;
        if ($daysSinceUpdate <= 365) return 0.2;

        return 0.1;
    }

    private function scoreBusinessPriority(SearchResultDTO $result): float
    {
        $isFeatured = $result->metadata['is_featured'] ?? false;
        $priority = $result->metadata['priority'] ?? 0;

        $score = $priority > 0 ? min(1.0, $priority / 10) : 0.5;

        if ($isFeatured) {
            $score = min(1.0, $score + 0.3);
        }

        return $score;
    }

    private function extractWords(string $text): array
    {
        $text = mb_strtolower(trim($text));
        $words = str_word_count($text, 1);
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for',
            'of', 'and', 'or', 'but', 'it', 'its', 'this', 'that', 'with', 'from', 'by'];

        return array_values(array_unique(array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords))));
    }
}
