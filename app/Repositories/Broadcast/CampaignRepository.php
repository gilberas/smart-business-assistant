<?php

namespace App\Repositories\Broadcast;

use App\Models\BroadcastCampaign;
use App\Models\Business;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignRepository
{
    public function paginate(Business $business, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BroadcastCampaign::query()
            ->where('business_id', $business->id)
            ->with(['template', 'creator'])
            ->withCount('recipients');

        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(Business $business, int $id): ?BroadcastCampaign
    {
        return BroadcastCampaign::query()
            ->where('business_id', $business->id)
            ->with(['template', 'creator', 'recipients.customer'])
            ->find($id);
    }

    public function findByStatus(Business $business, array $statuses): iterable
    {
        return BroadcastCampaign::query()
            ->where('business_id', $business->id)
            ->whereIn('status', $statuses)
            ->cursor();
    }

    public function create(array $data): BroadcastCampaign
    {
        return BroadcastCampaign::query()->create($data);
    }

    public function update(BroadcastCampaign $campaign, array $data): bool
    {
        return $campaign->update($data);
    }

    public function delete(BroadcastCampaign $campaign): bool
    {
        $campaign->recipients()->delete();
        return $campaign->delete();
    }

    public function duplicate(BroadcastCampaign $campaign, array $overrides = []): BroadcastCampaign
    {
        $data = $campaign->toArray();
        unset($data['id'], $data['uuid'], $data['created_at'], $data['updated_at']);
        $data['name'] = $campaign->name.' (Copy)';
        $data['status'] = 'draft';
        $data['scheduled_at'] = null;
        $data['started_at'] = null;
        $data['completed_at'] = null;
        $data = array_merge($data, $overrides);

        return $this->create($data);
    }

    public function countActive(Business $business): int
    {
        return BroadcastCampaign::query()
            ->where('business_id', $business->id)
            ->whereIn('status', ['running', 'scheduled'])
            ->count();
    }

    public function dailySentCount(Business $business): int
    {
        return BroadcastCampaign::query()
            ->where('business_id', $business->id)
            ->whereDate('created_at', today())
            ->withCount('recipients')
            ->get()
            ->sum('recipients_count');
    }
}
