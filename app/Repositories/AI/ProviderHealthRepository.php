<?php

namespace App\Repositories\AI;

use App\Models\AI\ProviderHealth;
use Illuminate\Database\Eloquent\Collection;

class ProviderHealthRepository
{
    public function __construct(
        private readonly ProviderHealth $model,
    ) {}

    public function latestByProvider(int $providerId): ?ProviderHealth
    {
        return $this->model->where('ai_provider_id', $providerId)
            ->orderBy('checked_at', 'desc')
            ->first();
    }

    public function historyByProvider(int $providerId, int $limit = 20): Collection
    {
        return $this->model->where('ai_provider_id', $providerId)
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): ProviderHealth
    {
        return $this->model->create($data);
    }
}
