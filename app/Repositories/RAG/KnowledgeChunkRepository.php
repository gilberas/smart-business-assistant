<?php

namespace App\Repositories\RAG;

use App\Models\RAG\KnowledgeChunk;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class KnowledgeChunkRepository
{
    public function __construct(
        private readonly KnowledgeChunk $model,
    ) {}

    public function findById(int $id): ?KnowledgeChunk
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?KnowledgeChunk
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByBusiness(int $businessId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (!empty($filters['strategy'])) {
            $query->where('strategy', $filters['strategy']);
        }

        if (!empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findBySource(int $businessId, string $sourceType, ?int $sourceId = null): Collection
    {
        $query = $this->model->where('business_id', $businessId)
            ->where('source_type', $sourceType);

        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }

        return $query->orderBy('chunk_index')->get();
    }

    public function create(array $data): KnowledgeChunk
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->find($id)?->update($data) ?? false;
    }

    public function delete(int $id): bool
    {
        return $this->model->find($id)?->delete() ?? false;
    }

    public function deleteBySource(int $businessId, string $sourceType, int $sourceId): bool
    {
        return $this->model->where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete() > 0;
    }

    public function countByBusiness(int $businessId): int
    {
        return $this->model->where('business_id', $businessId)->count();
    }

    public function countBySource(int $businessId, string $sourceType): int
    {
        return $this->model->where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->count();
    }
}
