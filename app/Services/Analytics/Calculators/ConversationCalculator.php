<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\ConversationEvent;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ConversationCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'conversation_summary', $filter, function () use ($business, $filter) {
            $query = Conversation::where('business_id', $business->id);
            $this->applyDateRange($query, $filter);
            $prevFilter = $this->previousPeriod($filter);
            $prevQuery = Conversation::where('business_id', $business->id);
            $this->applyDateRange($prevQuery, $prevFilter);

            $total = (clone $query)->count();
            $prevTotal = (clone $prevQuery)->count();
            $totalChange = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : null;

            $open = (clone $query)->where('status', 'open')->count();
            $closed = (clone $query)->where('status', 'closed')->count();
            $aiHandled = (clone $query)->where('handover_mode', 'ai')->count();
            $humanHandled = (clone $query)->where('handover_mode', 'human')->count();
            $handoverCount = \App\Models\HandoverLog::where('business_id', $business->id)
                ->where('handover_type', 'ai_to_human')
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->count();
            $handoverRate = $total > 0 ? round(($handoverCount / $total) * 100, 1) : 0;
            $resolutionRate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;

            $avgDuration = (clone $query)
                ->whereNotNull('closed_at')
                ->select(DB::raw('AVG(' . $this->dateDiffSql('created_at', 'closed_at', 'MINUTE') . ') as avg_duration'))
                ->value('avg_duration');

            $peakHours = Message::where('business_id', $business->id)
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->select(DB::raw($this->hourSql('created_at') . ' as hour'), DB::raw('COUNT(*) as count'))
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->pluck('count', 'hour');

            $peakDays = Message::where('business_id', $business->id)
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->select(DB::raw($this->dayNameSql('created_at') . ' as day'), DB::raw('COUNT(*) as count'))
                ->groupBy('day')
                ->orderBy('count', 'desc')
                ->pluck('count', 'day');

            $trend = $this->conversationTrend($business, $filter);
            $breakdown = $this->statusBreakdown($business, $filter);

            $reopened = ConversationEvent::whereIn('conversation_id', (clone $query)->pluck('id'))
                ->where('type', 'reopened')
                ->count();

            return [
                'total' => $total,
                'total_change_percent' => $totalChange,
                'open' => $open,
                'closed' => $closed,
                'reopened' => $reopened,
                'ai_handled' => $aiHandled,
                'human_handled' => $humanHandled,
                'handover_count' => $handoverCount,
                'handover_rate' => $handoverRate,
                'resolution_rate' => $resolutionRate,
                'avg_duration_minutes' => $avgDuration ? round((float) $avgDuration, 1) : null,
                'peak_hours' => $peakHours->toArray(),
                'peak_days' => $peakDays->toArray(),
                'trend' => $trend,
                'breakdown' => $breakdown,
            ];
        });
    }

    protected function conversationTrend(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = Conversation::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);
        $data = $this->timeSeries($query, 'created_at', $filter->groupBy);

        return [
            'labels' => array_keys($data),
            'datasets' => [['label' => 'Conversations', 'data' => array_values($data)]],
            'type' => 'line',
        ];
    }

    protected function statusBreakdown(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = Conversation::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);

        $breakdown = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'labels' => $breakdown->keys()->toArray(),
            'datasets' => [['label' => 'Status', 'data' => $breakdown->values()->toArray()]],
            'type' => 'doughnut',
        ];
    }
}
