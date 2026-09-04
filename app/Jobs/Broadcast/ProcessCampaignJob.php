<?php

namespace App\Jobs\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use App\Events\Broadcast\CampaignCompleted;
use App\Events\Broadcast\CampaignStarted;
use App\Models\BroadcastCampaign;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Services\Broadcast\DeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly BroadcastCampaign $campaign,
    ) {}

    public function handle(
        BroadcastRecipientRepository $recipientRepo,
        DeliveryService $deliveryService,
    ): void {
        if ($this->campaign->status !== BroadcastCampaignStatus::Scheduled && $this->campaign->status !== BroadcastCampaignStatus::Running) {
            Log::warning('Campaign not in processable state', [
                'campaign_id' => $this->campaign->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        DB::transaction(function () use ($recipientRepo) {
            $this->campaign->update([
                'status' => BroadcastCampaignStatus::Running->value,
                'started_at' => now(),
            ]);
        });

        CampaignStarted::dispatch($this->campaign);

        $batchSize = config('broadcast.sending.batch_size', 100);
        $totalDispatched = 0;
        $totalFailed = 0;

        do {
            if ($this->campaign->fresh()->status === BroadcastCampaignStatus::Paused->value
                || $this->campaign->fresh()->status === BroadcastCampaignStatus::Cancelled->value) {
                Log::info('Campaign processing stopped', [
                    'campaign_id' => $this->campaign->id,
                    'status' => $this->campaign->fresh()->status,
                ]);
                return;
            }

            $recipients = $recipientRepo->getPendingChunk($this->campaign, $batchSize);
            $chunkCount = 0;

            foreach ($recipients as $recipient) {
                $recipientRepo->markAsQueued($recipient);
                SendBroadcastJob::dispatch($recipient);
                $chunkCount++;
            }

            $totalDispatched += $chunkCount;

            if ($chunkCount < $batchSize) {
                break;
            }

            usleep(100000);
        } while (true);

        DB::transaction(function () use ($recipientRepo, $totalDispatched) {
            $pendingCount = BroadcastRecipientRepository::class
                ? BroadcastRecipientRepository::class
                : 0;

            $remaining = BroadcastCampaign::query()
                ->where('id', $this->campaign->id)
                ->first()
                ?->recipients()
                ->whereIn('status', ['pending', 'queued'])
                ->count() ?? 0;

            if ($remaining === 0) {
                $counts = $recipientRepo->statusCounts($this->campaign);
                $this->campaign->update([
                    'status' => BroadcastCampaignStatus::Completed->value,
                    'completed_at' => now(),
                ]);
                CampaignCompleted::dispatch($this->campaign, $counts);
            }
        });

        Log::info('Campaign processing completed', [
            'campaign_id' => $this->campaign->id,
            'dispatched' => $totalDispatched,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Campaign processing failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $e->getMessage(),
        ]);

        try {
            $this->campaign->update([
                'status' => BroadcastCampaignStatus::Failed->value,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $inner) {
            Log::error('Failed to update campaign status after failure', [
                'campaign_id' => $this->campaign->id,
                'error' => $inner->getMessage(),
            ]);
        }
    }
}
