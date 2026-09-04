<?php

namespace App\Repositories\AI;

use App\Models\AI\AIConfiguration;

class AIConfigurationRepository
{
    public function __construct(
        private readonly AIConfiguration $model,
    ) {}

    public function findByBusiness(int $businessId): ?AIConfiguration
    {
        return $this->model->where('business_id', $businessId)->first();
    }

    public function updateOrCreate(int $businessId, array $data): AIConfiguration
    {
        return $this->model->updateOrCreate(
            ['business_id' => $businessId],
            $data,
        );
    }

    public function create(array $data): AIConfiguration
    {
        return $this->model->create($data);
    }

    public function update(AIConfiguration $configuration, array $data): bool
    {
        return $configuration->update($data);
    }
}
