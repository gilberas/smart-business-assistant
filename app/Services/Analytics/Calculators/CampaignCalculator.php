<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class CampaignCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'campaign_summary', $filter, function () use ($business, $filter) {
            $query = BroadcastCampaign::where('business_id', $business->id);
            $this->applyDateRange($query, $filter);

            $totalCampaigns = (clone $query)->count();
            $sent = (clone $query)->whereNotNull('sent_at')->count();
            $draft = (clone $query)->where('status', 'draft')->count();
            $scheduled = (clone $query)->where('status', 'scheduled')->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            $failed = (clone $query)->where('status', 'failed')->count();

            $recipientQuery = BroadcastRecipient::whereIn('campaign_id', (clone $query)->pluck('id'));

            $totalRecipients = (clone $recipientQuery)->count();
            $sentRecipients = (clone $recipientQuery)->whereIn('status', ['sent', 'delivered', 'read'])->count();
            $delivered = (clone $recipientQuery)->whereIn('status', ['delivered', 'read'])->count();
            $read = (clone $recipientQuery)->where('status', 'read')->count();
            $failedRecipients = (clone $recipientQuery)->where('status', 'failed')->count();

            $deliveryRate = $totalRecipients > 0 ? round(($delivered / $totalRecipients) * 100, 1) : 0;
            $readRate = $totalRecipients > 0 ? round(($read / $totalRecipients) * 100, 1) : 0;
            $failureRate = $totalRecipients > 0 ? round(($failedRecipients / $totalRecipients) * 100, 1) : 0;

            $campaignPerformance = (clone $query)
                ->select('id', 'name', 'status')
                ->withCount(['recipients as total_recipients'])
                ->withCount(['recipients as sent_recipients' => fn($q) => $q->whereIn('status', ['sent', 'delivered', 'read'])])
                ->withCount(['recipients as delivered_recipients' => fn($q) => $q->whereIn('status', ['delivered', 'read'])])
                ->withCount(['recipients as read_recipients' => fn($q) => $q->where('status', 'read')])
                ->withCount(['recipients as failed_recipients' => fn($q) => $q->where('status', 'failed')])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return [
                'total_campaigns' => $totalCampaigns,
                'sent' => $sent,
                'draft' => $draft,
                'scheduled' => $scheduled,
                'completed' => $completed,
                'failed_campaigns' => $failed,
                'total_recipients' => $totalRecipients,
                'sent_recipients' => $sentRecipients,
                'delivered' => $delivered,
                'read' => $read,
                'failed_recipients' => $failedRecipients,
                'delivery_rate' => $deliveryRate,
                'read_rate' => $readRate,
                'failure_rate' => $failureRate,
                'campaign_performance' => $campaignPerformance->toArray(),
            ];
        });
    }
}
