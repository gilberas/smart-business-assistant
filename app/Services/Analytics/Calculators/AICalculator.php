<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\AiRequestLog;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class AICalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'ai_summary', $filter, function () use ($business, $filter) {
            $query = AiRequestLog::where('business_id', $business->id);
            $this->applyDateRange($query, $filter);

            $totalRequests = (clone $query)->count();
            $successful = (clone $query)->where('response_status', 'success')->count();
            $failed = (clone $query)->where('response_status', 'failed')->count();
            $successRate = $totalRequests > 0 ? round(($successful / $totalRequests) * 100, 1) : 0;

            $tokens = (clone $query)
                ->select(
                    DB::raw('COALESCE(SUM(prompt_tokens), 0) as prompt_tokens'),
                    DB::raw('COALESCE(SUM(completion_tokens), 0) as completion_tokens'),
                    DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'),
                    DB::raw('COALESCE(SUM(cost), 0) as total_cost'),
                    DB::raw('COALESCE(AVG(duration_ms), 0) as avg_latency'),
                )
                ->first();

            $byProvider = (clone $query)
                ->select('provider', DB::raw('COUNT(*) as count'))
                ->groupBy('provider')
                ->orderBy('count', 'desc')
                ->pluck('count', 'provider');

            $byModel = (clone $query)
                ->select('model', DB::raw('COUNT(*) as count'))
                ->groupBy('model')
                ->orderBy('count', 'desc')
                ->pluck('count', 'model');

            $trend = $this->requestTrend($business, $filter);

            $convsQuery = \App\Models\Conversation::where('business_id', $business->id);
            $this->applyDateRange($convsQuery, $filter);
            $totalConvs = (clone $convsQuery)->count();
            $aiConvs = (clone $convsQuery)->where('handover_mode', 'ai')->count();
            $handoverConvs = (clone $convsQuery)->where('handover_mode', 'human')
                ->where('handover_reason', '!=', '')->count();
            $handoverCount = \App\Models\HandoverLog::where('business_id', $business->id)
                ->where('handover_type', 'ai_to_human')
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->count();

            $aiResolutionRate = $totalConvs > 0 ? round(($aiConvs / $totalConvs) * 100, 1) : 0;
            $handoverRate = $totalConvs > 0 ? round(($handoverCount / $totalConvs) * 100, 1) : 0;

            $lowConfidence = (clone $query)->where('response_status', 'failed')->count();

            return [
                'total_requests' => $totalRequests,
                'successful_requests' => $successful,
                'failed_requests' => $failed,
                'success_rate' => $successRate,
                'prompt_tokens' => (int) ($tokens?->prompt_tokens ?? 0),
                'completion_tokens' => (int) ($tokens?->completion_tokens ?? 0),
                'total_tokens' => (int) ($tokens?->total_tokens ?? 0),
                'estimated_cost' => (float) ($tokens?->total_cost ?? 0),
                'avg_latency_ms' => (int) ($tokens?->avg_latency ?? 0),
                'ai_resolution_rate' => $aiResolutionRate,
                'handover_rate' => $handoverRate,
                'handover_count' => $handoverCount,
                'low_confidence_responses' => $lowConfidence,
                'by_provider' => $byProvider->toArray(),
                'by_model' => $byModel->toArray(),
                'trend' => $trend,
            ];
        });
    }

    protected function requestTrend(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = AiRequestLog::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);

        $all = $this->timeSeries((clone $query), 'created_at', $filter->groupBy);
        $success = $this->timeSeries((clone $query)->where('response_status', 'success'), 'created_at', $filter->groupBy);
        $failed = $this->timeSeries((clone $query)->where('response_status', 'failed'), 'created_at', $filter->groupBy);

        $labels = array_keys($all);
        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'All Requests', 'data' => array_values($all)],
                ['label' => 'Successful', 'data' => array_values($success)],
                ['label' => 'Failed', 'data' => array_values($failed)],
            ],
            'type' => 'line',
        ];
    }
}
