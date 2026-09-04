<?php

namespace App\DTOs\Analytics;

class OverviewDTO
{
    public function __construct(
        public readonly array $kpis,
        public readonly ChartDataDTO $conversationTrend,
        public readonly ChartDataDTO $customerTrend,
        public readonly ChartDataDTO $channelDistribution,
        public readonly ChartDataDTO $aiPerformance,
        public readonly ChartDataDTO $topProducts,
        public readonly array $recentActivity,
    ) {}

    public function toArray(): array
    {
        return [
            'kpis' => array_map(fn($k) => $k instanceof KpiDTO ? $k->toArray() : $k, $this->kpis),
            'conversation_trend' => $this->conversationTrend->toArray(),
            'customer_trend' => $this->customerTrend->toArray(),
            'channel_distribution' => $this->channelDistribution->toArray(),
            'ai_performance' => $this->aiPerformance->toArray(),
            'top_products' => $this->topProducts->toArray(),
            'recent_activity' => $this->recentActivity,
        ];
    }
}
