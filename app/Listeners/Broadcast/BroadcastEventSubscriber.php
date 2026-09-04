<?php

namespace App\Listeners\Broadcast;

use App\Events\Broadcast\CampaignCancelled;
use App\Events\Broadcast\CampaignCompleted;
use App\Events\Broadcast\CampaignPaused;
use App\Events\Broadcast\CampaignResumed;
use App\Events\Broadcast\CampaignScheduled;
use App\Events\Broadcast\CampaignStarted;
use App\Events\Broadcast\RecipientStatusUpdated;
use Illuminate\Support\Facades\Log;

class BroadcastEventSubscriber
{
    public function handleCampaignScheduled(CampaignScheduled $event): void
    {
        Log::info('Campaign scheduled', [
            'campaign_id' => $event->campaign->id,
            'scheduled_at' => $event->campaign->scheduled_at,
        ]);
    }

    public function handleCampaignStarted(CampaignStarted $event): void
    {
        Log::info('Campaign started', [
            'campaign_id' => $event->campaign->id,
        ]);
    }

    public function handleCampaignCompleted(CampaignCompleted $event): void
    {
        Log::info('Campaign completed', [
            'campaign_id' => $event->campaign->id,
            'summary' => $event->summary,
        ]);
    }

    public function handleCampaignPaused(CampaignPaused $event): void
    {
        Log::info('Campaign paused', [
            'campaign_id' => $event->campaign->id,
        ]);
    }

    public function handleCampaignResumed(CampaignResumed $event): void
    {
        Log::info('Campaign resumed', [
            'campaign_id' => $event->campaign->id,
        ]);
    }

    public function handleCampaignCancelled(CampaignCancelled $event): void
    {
        Log::info('Campaign cancelled', [
            'campaign_id' => $event->campaign->id,
        ]);
    }

    public function handleRecipientStatusUpdated(RecipientStatusUpdated $event): void
    {
        Log::debug('Recipient status updated', [
            'recipient_id' => $event->recipient->id,
            'from' => $event->oldStatus,
            'to' => $event->newStatus,
        ]);
    }

    public function subscribe($events): void
    {
        $events->listen(CampaignScheduled::class, [self::class, 'handleCampaignScheduled']);
        $events->listen(CampaignStarted::class, [self::class, 'handleCampaignStarted']);
        $events->listen(CampaignCompleted::class, [self::class, 'handleCampaignCompleted']);
        $events->listen(CampaignPaused::class, [self::class, 'handleCampaignPaused']);
        $events->listen(CampaignResumed::class, [self::class, 'handleCampaignResumed']);
        $events->listen(CampaignCancelled::class, [self::class, 'handleCampaignCancelled']);
        $events->listen(RecipientStatusUpdated::class, [self::class, 'handleRecipientStatusUpdated']);
    }
}
