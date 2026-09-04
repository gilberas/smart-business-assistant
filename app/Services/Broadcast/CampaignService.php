<?php

namespace App\Services\Broadcast;

use App\Contracts\Broadcast\CampaignServiceInterface;
use App\Enums\Broadcast\BroadcastCampaignStatus;
use App\Models\BroadcastCampaign;
use App\Models\Business;
use App\Models\User;
use App\Repositories\Broadcast\BroadcastRecipientRepository;
use App\Repositories\Broadcast\CampaignRepository;
use App\Repositories\Broadcast\TemplateRepository;
use App\Services\Broadcast\AudienceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService implements CampaignServiceInterface
{
    public function __construct(
        private readonly CampaignRepository $campaignRepo,
        private readonly BroadcastRecipientRepository $recipientRepo,
        private readonly TemplateRepository $templateRepo,
        private readonly AudienceService $audienceService,
    ) {}

    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->campaignRepo->paginate($business, $filters, $perPage);
    }

    public function find(Business $business, int $id): ?BroadcastCampaign
    {
        return $this->campaignRepo->findById($business, $id);
    }

    public function create(Business $business, User $user, array $data): BroadcastCampaign
    {
        $campaign = $this->campaignRepo->create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'template_id' => $data['template_id'] ?? null,
            'message_content' => $data['message_content'] ?? null,
            'recipient_filter' => $data['recipient_filter'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $user->id,
            'metadata' => $data['metadata'] ?? null,
        ]);

        if (!empty($data['recipient_filter'])) {
            $count = $this->audienceService->count($business, $data['recipient_filter']);
            $campaign->metadata = array_merge($campaign->metadata ?? [], ['estimated_recipients' => $count]);
            $campaign->save();
        }

        return $campaign->load(['template', 'creator']);
    }

    public function update(BroadcastCampaign $campaign, array $data): BroadcastCampaign
    {
        $this->campaignRepo->update($campaign, $data);
        return $campaign->fresh(['template', 'creator']);
    }

    public function delete(BroadcastCampaign $campaign): bool
    {
        return $this->campaignRepo->delete($campaign);
    }

    public function duplicate(BroadcastCampaign $campaign): BroadcastCampaign
    {
        return $this->campaignRepo->duplicate($campaign);
    }

    public function schedule(BroadcastCampaign $campaign): BroadcastCampaign
    {
        if ($campaign->status !== BroadcastCampaignStatus::Draft) {
            throw new \RuntimeException('Only draft campaigns can be scheduled');
        }

        $this->validateCampaignReady($campaign);
        $this->campaignRepo->update($campaign, [
            'status' => BroadcastCampaignStatus::Scheduled->value,
            'scheduled_at' => $campaign->scheduled_at ?? now(),
        ]);

        \App\Events\Broadcast\CampaignScheduled::dispatch($campaign);
        Log::info('Campaign scheduled', ['campaign_id' => $campaign->id, 'scheduled_at' => $campaign->scheduled_at]);

        return $campaign->fresh(['template', 'creator']);
    }

    public function pause(BroadcastCampaign $campaign): BroadcastCampaign
    {
        if ($campaign->status !== BroadcastCampaignStatus::Running) {
            throw new \RuntimeException('Only running campaigns can be paused');
        }

        $this->campaignRepo->update($campaign, ['status' => BroadcastCampaignStatus::Paused->value]);
        \App\Events\Broadcast\CampaignPaused::dispatch($campaign);

        return $campaign->fresh(['template', 'creator']);
    }

    public function resume(BroadcastCampaign $campaign): BroadcastCampaign
    {
        if ($campaign->status !== BroadcastCampaignStatus::Paused) {
            throw new \RuntimeException('Only paused campaigns can be resumed');
        }

        $this->campaignRepo->update($campaign, ['status' => BroadcastCampaignStatus::Running->value]);
        \App\Events\Broadcast\CampaignResumed::dispatch($campaign);

        return $campaign->fresh(['template', 'creator']);
    }

    public function cancel(BroadcastCampaign $campaign): BroadcastCampaign
    {
        if ($campaign->status->isTerminal()) {
            throw new \RuntimeException('Cannot cancel a campaign that has already completed');
        }

        $this->campaignRepo->update($campaign, [
            'status' => BroadcastCampaignStatus::Cancelled->value,
            'completed_at' => now(),
        ]);
        \App\Events\Broadcast\CampaignCancelled::dispatch($campaign);

        return $campaign->fresh(['template', 'creator']);
    }

    public function archive(BroadcastCampaign $campaign): BroadcastCampaign
    {
        $this->campaignRepo->update($campaign, ['status' => BroadcastCampaignStatus::Archived->value]);

        return $campaign->fresh(['template', 'creator']);
    }

    public function stats(BroadcastCampaign $campaign): array
    {
        $counts = $this->recipientRepo->statusCounts($campaign);

        return \App\DTOs\Broadcast\CampaignStatsDTO::fromCounts(
            total: $counts['total'],
            statusCounts: $counts,
        )->toArray();
    }

    private function validateCampaignReady(BroadcastCampaign $campaign): void
    {
        if (!$campaign->template_id && !$campaign->message_content) {
            throw new \RuntimeException('Campaign must have a template or message content');
        }

        if (empty($campaign->recipient_filter)) {
            throw new \RuntimeException('Campaign must have a recipient filter defined');
        }

        $activeCount = $this->campaignRepo->countActive($campaign->business);
        if ($activeCount >= config('broadcast.limits.concurrent_campaigns', 3)) {
            throw new \RuntimeException('Maximum number of concurrent campaigns reached');
        }
    }
}
