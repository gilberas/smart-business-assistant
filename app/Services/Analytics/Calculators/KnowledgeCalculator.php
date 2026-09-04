<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\RAG\RetrievalLog;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class KnowledgeCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'knowledge_summary', $filter, function () use ($business, $filter) {
            $articles = KnowledgeArticle::where('business_id', $business->id);
            $totalArticles = (clone $articles)->count();
            $publishedArticles = (clone $articles)->where('status', 'published')->count();
            $totalViews = (clone $articles)->sum('view_count');
            $totalHelpful = (clone $articles)->sum('helpful_count');
            $totalNotHelpful = (clone $articles)->sum('not_helpful_count');

            $helpfulRate = ($totalHelpful + $totalNotHelpful) > 0
                ? round(($totalHelpful / ($totalHelpful + $totalNotHelpful)) * 100, 1)
                : null;

            $topArticles = (clone $articles)
                ->where('status', 'published')
                ->orderBy('view_count', 'desc')
                ->limit(10)
                ->get(['id', 'title', 'view_count', 'helpful_count', 'not_helpful_count']);

            $needImprovement = (clone $articles)
                ->where('status', 'published')
                ->where('not_helpful_count', '>', 0)
                ->whereColumn('not_helpful_count', '>=', DB::raw('helpful_count'))
                ->orderBy('not_helpful_count', 'desc')
                ->limit(10)
                ->get(['id', 'title', 'view_count', 'helpful_count', 'not_helpful_count']);

            $faqCount = KnowledgeFaq::where('business_id', $business->id)
                ->where('is_published', true)
                ->count();

            $retrievalLogs = RetrievalLog::where('business_id', $business->id)
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);

            $totalSearches = (clone $retrievalLogs)->count();
            $avgResults = (clone $retrievalLogs)->avg('total_results');

            $topQueries = (clone $retrievalLogs)
                ->select('query', DB::raw('COUNT(*) as count'), DB::raw('AVG(total_results) as avg_results'))
                ->groupBy('query')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            $zeroResultQueries = (clone $retrievalLogs)
                ->where('total_results', 0)
                ->count();

            $messages = Message::where('business_id', $business->id)
                ->where('direction', 'inbound')
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);

            $totalQuestions = (clone $messages)->where('message_type', 'text')->count();

            $aiHandoverQuery = \App\Models\Conversation::where('business_id', $business->id)
                ->where('handover_mode', 'human')
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->whereNotNull('handover_reason');

            $frequentHandoverTopics = (clone $aiHandoverQuery)
                ->select('handover_reason', DB::raw('COUNT(*) as count'))
                ->whereNotNull('handover_reason')
                ->groupBy('handover_reason')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'handover_reason');

            return [
                'total_articles' => $totalArticles,
                'published_articles' => $publishedArticles,
                'total_views' => $totalViews,
                'total_helpful' => $totalHelpful,
                'total_not_helpful' => $totalNotHelpful,
                'helpful_rate' => $helpfulRate,
                'total_faqs' => $faqCount,
                'total_searches' => $totalSearches,
                'avg_results_per_search' => $avgResults ? round((float) $avgResults, 1) : 0,
                'zero_result_queries' => $zeroResultQueries,
                'unanswered_rate' => $totalSearches > 0 ? round(($zeroResultQueries / $totalSearches) * 100, 1) : 0,
                'top_articles' => $topArticles->toArray(),
                'need_improvement' => $needImprovement->toArray(),
                'top_queries' => $topQueries->toArray(),
                'frequent_handover_topics' => $frequentHandoverTopics->toArray(),
            ];
        });
    }
}
