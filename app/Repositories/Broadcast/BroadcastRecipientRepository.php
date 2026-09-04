<?php

namespace App\Repositories\Broadcast;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

class BroadcastRecipientRepository
{
    public function paginate(BroadcastCampaign $campaign, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BroadcastRecipient::query()
            ->where('broadcast_campaign_id', $campaign->id)
            ->with('customer');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortField = $filters['sort_field'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage);
    }

    public function createBatch(BroadcastCampaign $campaign, array $customerIds): int
    {
        $now = now();
        $records = [];
        foreach ($customerIds as $customerId) {
            $records[] = [
                'broadcast_campaign_id' => $campaign->id,
                'customer_id' => $customerId,
                'status' => 'pending',
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return BroadcastRecipient::query()->insert($records) ? count($records) : 0;
    }

    public function getPendingChunk(BroadcastCampaign $campaign, int $limit): LazyCollection
    {
        return BroadcastRecipient::query()
            ->where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->with('customer')
            ->limit($limit)
            ->cursor();
    }

    public function markAsQueued(BroadcastRecipient $recipient): void
    {
        $recipient->update(['status' => 'queued', 'queued_at' => now()]);
    }

    public function markAsSent(BroadcastRecipient $recipient, ?string $messageId = null): void
    {
        $recipient->update([
            'status' => 'sent',
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(BroadcastRecipient $recipient): void
    {
        $recipient->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markAsRead(BroadcastRecipient $recipient): void
    {
        $recipient->update(['status' => 'read', 'read_at' => now()]);
    }

    public function markAsFailed(BroadcastRecipient $recipient, ?string $error = null): void
    {
        $recipient->update([
            'status' => 'failed',
            'error_message' => $error,
            'failed_at' => now(),
        ]);
    }

    public function statusCounts(BroadcastCampaign $campaign): array
    {
        return BroadcastRecipient::query()
            ->where('broadcast_campaign_id', $campaign->id)
            ->selectRaw("COALESCE(COUNT(*), 0) as total")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END), 0) as queued")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END), 0) as sent")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END), 0) as read")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END), 0) as expired")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected")
            ->first()
            ->toArray();
    }

    public function countFailed(BroadcastCampaign $campaign): int
    {
        return BroadcastRecipient::query()
            ->where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->count();
    }
}
