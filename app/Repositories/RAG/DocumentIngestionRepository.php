<?php

namespace App\Repositories\RAG;

use App\Enums\RAG\IngestionStatus;
use App\Models\RAG\DocumentIngestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentIngestionRepository
{
    public function __construct(
        private readonly DocumentIngestion $model,
    ) {}

    public function findById(int $id): ?DocumentIngestion
    {
        return $this->model->find($id);
    }

    public function findByBusiness(int $businessId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['ingestible_type'])) {
            $query->where('ingestible_type', $filters['ingestible_type']);
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByIngestible(string $type, int $id): ?DocumentIngestion
    {
        return $this->model->where('ingestible_type', $type)
            ->where('ingestible_id', $id)
            ->latest()
            ->first();
    }

    public function create(array $data): DocumentIngestion
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->find($id)?->update($data) ?? false;
    }

    public function pending(): Collection
    {
        return $this->model->where('status', IngestionStatus::Pending)->get();
    }

    public function failed(): Collection
    {
        return $this->model->where('status', IngestionStatus::Failed)->get();
    }
}
