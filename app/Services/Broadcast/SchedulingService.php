<?php

namespace App\Services\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use App\Models\BroadcastCampaign;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Repositories\Broadcast\CampaignRepository;
use Illuminate\Support\Facades\Log;

class SchedulingService
{
    public function __construct(
        private readonly CampaignRepository $campaignRepo,
        private readonly BroadcastRecipientRepository $recipientRepo,
        private readonly CampaignService $campaignService,
    ) {}

    public function processDueCampaigns(): void
    {
        $campaigns = BroadcastCampaign::query()
            ->where('status', BroadcastCampaignStatus::Scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->cursor();

        $processed = 0;
        foreach ($campaigns as $campaign) {
            try {
                $this->campaignService->schedule($campaign);
                dispatch(new \App\Jobs\Broadcast\ProcessCampaignJob($campaign));
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Failed to process scheduled campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($processed > 0) {
            Log::info('Scheduled campaigns processed', ['count' => $processed]);
        }
    }
}
