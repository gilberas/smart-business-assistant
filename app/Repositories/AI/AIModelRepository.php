<?php

namespace App\Repositories\AI;

use App\Models\AI\AIModel;
use Illuminate\Database\Eloquent\Collection;

class AIModelRepository
{
    public function __construct(
        private readonly AIModel $model,
    ) {}

    public function all(): Collection
    {
        return $this->model->with('provider')->orderBy('ai_provider_id')->get();
    }

    public function active(): Collection
    {
        return $this->model->active()->with('provider')->get();
    }

    public function findByProvider(int $providerId): Collection
    {
        return $this->model->where('ai_provider_id', $providerId)->active()->get();
    }

    public function findById(int $id): ?AIModel
    {
        return $this->model->with('provider')->find($id);
    }

    public function findByModelId(int $providerId, string $modelId): ?AIModel
    {
        return $this->model->where('ai_provider_id', $providerId)->where('model_id', $modelId)->first();
    }

    public function create(array $data): AIModel
    {
        return $this->model->create($data);
    }

    public function updateOrCreate(array $attributes, array $values): AIModel
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    public function delete(AIModel $model): bool
    {
        return $model->delete();
    }

    public function deactivateByProvider(int $providerId): void
    {
        $this->model->where('ai_provider_id', $providerId)->update(['is_active' => false]);
    }
}
