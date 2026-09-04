<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\DTOs\Analytics\ChartDataDTO;
use App\DTOs\Analytics\KpiDTO;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\HandoverLog;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class OverviewCalculator extends BaseCalculator
{
    public function calculate(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'overview', $filter, function () use ($business, $filter) {
            $conversationQuery = Conversation::where('business_id', $business->id);
            $this->applyDateRange($conversationQuery, $filter);
            $prevFilter = $this->previousPeriod($filter);

            $prevConvQuery = Conversation::where('business_id', $business->id);
            $this->applyDateRange($prevConvQuery, $prevFilter);

            $kpis = [
                $this->kpi($business, 'total_conversations', 'Total Conversations',
                    fn() => (clone $conversationQuery)->count(),
                    fn() => (clone $prevConvQuery)->count(), 'number', 'conversation'),

                $this->kpi($business, 'new_conversations', 'New',
                    fn() => (clone $conversationQuery)->count(),
                    fn() => (clone $prevConvQuery)->count(), 'number', 'plus'),

                $this->kpi($business, 'open_conversations', 'Open',
                    fn() => Conversation::where('business_id', $business->id)->where('status', 'open')->count(),
                    null, 'number', 'circle'),

                $this->kpi($business, 'resolved_conversations', 'Resolved',
                    fn() => (clone $conversationQuery)->where('status', 'closed')->count(),
                    fn() => (clone $prevConvQuery)->where('status', 'closed')->count(), 'number', 'check'),

                $this->kpi($business, 'total_customers', 'Total Customers',
                    fn() => \App\Models\Customer::where('business_id', $business->id)->count(),
                    fn() => \App\Models\Customer::where('business_id', $business->id)->where('created_at', '<', $filter->dateRange->startCarbon())->count(),
                    'number', 'users'),

                $this->kpi($business, 'new_customers', 'New Customers',
                    fn() => \App\Models\Customer::where('business_id', $business->id)->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])->count(),
                    fn() => \App\Models\Customer::where('business_id', $business->id)->whereBetween('created_at', [$prevFilter->dateRange->startCarbon(), $prevFilter->dateRange->endCarbon()])->count(),
                    'number', 'userPlus'),

                $this->kpi($business, 'ai_handled', 'AI Handled',
                    fn() => (clone $conversationQuery)->where('handover_mode', 'ai')->count(),
                    fn() => (clone $prevConvQuery)->where('handover_mode', 'ai')->count(), 'number', 'bot'),

                $this->kpi($business, 'human_handled', 'Human Handled',
                    fn() => (clone $conversationQuery)->where('handover_mode', 'human')->count(),
                    fn() => (clone $prevConvQuery)->where('handover_mode', 'human')->count(), 'number', 'headset'),
            ];

            $totalConvs = (clone $conversationQuery)->count();
            $aiConvs = (clone $conversationQuery)->where('handover_mode', 'ai')->count();
            $aiResolutionRate = $totalConvs > 0 ? round(($aiConvs / $totalConvs) * 100, 1) : 0;
            $handoverCount = HandoverLog::where('business_id', $business->id)
                ->where('handover_type', 'ai_to_human')
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->count();
            $handoverRate = $totalConvs > 0 ? round(($handoverCount / $totalConvs) * 100, 1) : 0;

            $kpis[] = new KpiDTO('ai_resolution_rate', 'AI Resolution Rate', $aiResolutionRate, null, null, 'percent', 'brain');
            $kpis[] = new KpiDTO('handover_rate', 'Handover Rate', $handoverRate, null, null, 'percent', 'arrowRight');

            $msgQuery = Message::where('business_id', $business->id);
            $this->applyDateRange($msgQuery, $filter);
            $inbound = (clone $msgQuery)->where('direction', 'inbound')->count();
            $outbound = (clone $msgQuery)->where('direction', 'outgoing')->count();

            $kpis[] = new KpiDTO('messages_received', 'Messages Received', $inbound, null, null, 'number', 'messageCircle');
            $kpis[] = new KpiDTO('messages_sent', 'Messages Sent', $outbound, null, null, 'number', 'send');

            return [
                'kpis' => array_map(fn($k) => $k->toArray(), $kpis),
                'conversation_trend' => $this->conversationTrend($business, $filter),
                'customer_trend' => $this->customerTrend($business, $filter),
                'ai_performance' => $this->aiPerformance($business, $filter),
                'channel_distribution' => $this->channelDistribution($business, $filter),
                'message_volume' => $this->messageVolume($business, $filter),
            ];
        });
    }

    protected function conversationTrend(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = Conversation::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);

        $all = $this->timeSeries((clone $query), 'created_at', $filter->groupBy);
        $ai = $this->timeSeries((clone $query)->where('handover_mode', 'ai'), 'created_at', $filter->groupBy);
        $human = $this->timeSeries((clone $query)->where('handover_mode', 'human'), 'created_at', $filter->groupBy);

        $labels = array_keys($all);
        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'All Conversations', 'data' => array_values($all)],
                ['label' => 'AI Handled', 'data' => array_values($ai)],
                ['label' => 'Human Handled', 'data' => array_values($human)],
            ],
            'type' => 'line',
        ];
    }

    protected function customerTrend(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = \App\Models\Customer::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);
        $data = $this->timeSeries($query, 'created_at', $filter->groupBy);

        return [
            'labels' => array_keys($data),
            'datasets' => [['label' => 'New Customers', 'data' => array_values($data)]],
            'type' => 'line',
        ];
    }

    protected function aiPerformance(Business $business, AnalyticsFilterDTO $filter): array
    {
        $total = Conversation::where('business_id', $business->id);
        $this->applyDateRange($total, $filter);
        $totalData = $this->timeSeries($total, 'created_at', $filter->groupBy);

        $ai = Conversation::where('business_id', $business->id)->where('handover_mode', 'ai');
        $this->applyDateRange($ai, $filter);
        $aiData = $this->timeSeries($ai, 'created_at', $filter->groupBy);

        $labels = array_unique(array_merge(array_keys($totalData), array_keys($aiData)));
        sort($labels);

        $rateData = [];
        foreach ($labels as $label) {
            $t = $totalData[$label] ?? 0;
            $a = $aiData[$label] ?? 0;
            $rateData[] = $t > 0 ? round(($a / $t) * 100, 1) : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'AI Resolution Rate (%)', 'data' => $rateData],
            ],
            'type' => 'line',
        ];
    }

    protected function channelDistribution(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = Conversation::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);

        $channels = (clone $query)
            ->select('channel', DB::raw('COUNT(*) as count'))
            ->whereNotNull('channel')
            ->groupBy('channel')
            ->pluck('count', 'channel');

        return [
            'labels' => $channels->keys()->toArray(),
            'datasets' => [['label' => 'Channel Distribution', 'data' => $channels->values()->toArray()]],
            'type' => 'doughnut',
        ];
    }

    protected function messageVolume(Business $business, AnalyticsFilterDTO $filter): array
    {
        $inbound = Message::where('business_id', $business->id)->where('direction', 'inbound');
        $this->applyDateRange($inbound, $filter);
        $inData = $this->timeSeries($inbound, 'created_at', $filter->groupBy);

        $outbound = Message::where('business_id', $business->id)->where('direction', 'outgoing');
        $this->applyDateRange($outbound, $filter);
        $outData = $this->timeSeries($outbound, 'created_at', $filter->groupBy);

        $labels = array_unique(array_merge(array_keys($inData), array_keys($outData)));
        sort($labels);

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Inbound', 'data' => array_values(array_map(fn($l) => $inData[$l] ?? 0, $labels))],
                ['label' => 'Outbound', 'data' => array_values(array_map(fn($l) => $outData[$l] ?? 0, $labels))],
            ],
            'type' => 'line',
        ];
    }
}
