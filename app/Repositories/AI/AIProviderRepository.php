<?php

namespace App\Repositories\AI;

use App\Models\AI\AIProvider;
use Illuminate\Database\Eloquent\Collection;

class AIProviderRepository
{
    public function __construct(
        private readonly AIProvider $model,
    ) {}

    public function all(): Collection
    {
        return $this->model->with('models')->orderBy('priority')->get();
    }

    public function active(): Collection
    {
        return $this->model->active()->with('models')->orderBy('priority')->get();
    }

    public function findById(int $id): ?AIProvider
    {
        return $this->model->with('models')->find($id);
    }

    public function findBySlug(string $slug): ?AIProvider
    {
        return $this->model->where('slug', $slug)->with('models')->first();
    }

    public function default(): ?AIProvider
    {
        return $this->model->where('is_default', true)->with('models')->first();
    }

    public function create(array $data): AIProvider
    {
        return $this->model->create($data);
    }

    public function update(AIProvider $provider, array $data): bool
    {
        return $provider->update($data);
    }

    public function delete(AIProvider $provider): bool
    {
        return $provider->delete();
    }
}
