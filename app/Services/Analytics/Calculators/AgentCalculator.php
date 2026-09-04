<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\HandoverLog;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class AgentCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'agent_summary', $filter, function () use ($business, $filter) {
            $query = Conversation::where('business_id', $business->id);
            $this->applyDateRange($query, $filter);

            $agentIds = ConversationParticipant::whereIn('conversation_id', (clone $query)->pluck('id'))
                ->pluck('user_id')
                ->unique();

            $totalAssigned = (clone $query)->whereNotNull('assigned_to')->count();
            $totalResolved = (clone $query)->where('status', 'closed')->whereNotNull('assigned_to')->count();

            $agentStats = [];
            foreach ($agentIds as $agentId) {
                $agentConvs = Conversation::where('business_id', $business->id)
                    ->where('assigned_to', $agentId);
                $this->applyDateRange($agentConvs, $filter);

                $convCount = (clone $agentConvs)->count();
                $resolvedCount = (clone $agentConvs)->where('status', 'closed')->count();

                $msgQuery = Message::where('business_id', $business->id)
                    ->where('sender_type', 'agent')
                    ->where('sender_id', $agentId);
                $this->applyDateRange($msgQuery, $filter);
                $msgCount = (clone $msgQuery)->count();

                $firstResp = (clone $agentConvs)
                    ->whereNotNull('created_at')
                    ->whereNotNull('last_message_at')
                    ->select(DB::raw('AVG(' . $this->dateDiffSql('created_at', 'last_message_at', 'SECOND') . ') as avg_response'))
                    ->value('avg_response');

                $handovers = HandoverLog::where('business_id', $business->id)
                    ->where('assigned_to', $agentId)
                    ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                    ->count();

                $user = \App\Models\User::find($agentId);
                $agentStats[] = [
                    'agent_id' => $agentId,
                    'agent_name' => $user?->name ?? "Agent #{$agentId}",
                    'conversations_assigned' => $convCount,
                    'conversations_resolved' => $resolvedCount,
                    'resolution_rate' => $convCount > 0 ? round(($resolvedCount / $convCount) * 100, 1) : 0,
                    'messages_sent' => $msgCount,
                    'avg_first_response_seconds' => $firstResp ? round((float) $firstResp) : null,
                    'handover_count' => $handovers,
                ];
            }

            $allMsgQuery = Message::where('business_id', $business->id)->where('sender_type', 'agent');
            $this->applyDateRange($allMsgQuery, $filter);
            $totalAgentMessages = (clone $allMsgQuery)->count();

            $avgFirstResponse = (clone $query)
                ->whereNotNull('assigned_to')
                ->whereNotNull('last_message_at')
                ->select(DB::raw('AVG(' . $this->dateDiffSql('created_at', 'last_message_at', 'SECOND') . ') as avg_response'))
                ->value('avg_response');

            $resolutionTimeQuery = Conversation::where('business_id', $business->id)
                ->whereNotNull('assigned_to')
                ->whereNotNull('closed_at');
            $this->applyDateRange($resolutionTimeQuery, $filter);
            $avgResolutionTime = (clone $resolutionTimeQuery)
                ->select(DB::raw('AVG(' . $this->dateDiffSql('created_at', 'closed_at', 'MINUTE') . ') as avg_time'))
                ->value('avg_time');

            return [
                'total_assigned' => $totalAssigned,
                'total_resolved' => $totalResolved,
                'total_agent_messages' => $totalAgentMessages,
                'avg_first_response_seconds' => $avgFirstResponse ? round((float) $avgFirstResponse) : null,
                'avg_resolution_minutes' => $avgResolutionTime ? round((float) $avgResolutionTime, 1) : null,
                'agent_stats' => $agentStats,
            ];
        });
    }
}
