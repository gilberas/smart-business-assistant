<?php

namespace App\Contracts\Broadcast;

use App\Models\Business;
use App\Models\BroadcastCampaign;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignServiceInterface
{
    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator;

    public function find(Business $business, int $id): ?BroadcastCampaign;

    public function create(Business $business, User $user, array $data): BroadcastCampaign;

    public function update(BroadcastCampaign $campaign, array $data): BroadcastCampaign;

    public function delete(BroadcastCampaign $campaign): bool;

    public function duplicate(BroadcastCampaign $campaign): BroadcastCampaign;

    public function schedule(BroadcastCampaign $campaign): BroadcastCampaign;

    public function pause(BroadcastCampaign $campaign): BroadcastCampaign;

    public function resume(BroadcastCampaign $campaign): BroadcastCampaign;

    public function cancel(BroadcastCampaign $campaign): BroadcastCampaign;

    public function archive(BroadcastCampaign $campaign): BroadcastCampaign;

    public function stats(BroadcastCampaign $campaign): array;
}
