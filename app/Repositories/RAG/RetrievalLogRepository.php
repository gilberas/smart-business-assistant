<?php

namespace App\Repositories\RAG;

use App\Models\RAG\RetrievalLog;
use Illuminate\Pagination\LengthAwarePaginator;

class RetrievalLogRepository
{
    public function __construct(
        private readonly RetrievalLog $model,
    ) {}

    public function findById(int $id): ?RetrievalLog
    {
        return $this->model->find($id);
    }

    public function findByBusiness(int $businessId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['intent'])) {
            $query->where('intent', $filters['intent']);
        }

        if (!empty($filters['strategy'])) {
            $query->where('primary_strategy', $filters['strategy']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): RetrievalLog
    {
        return $this->model->create($data);
    }

    public function deleteOlderThan(int $days): int
    {
        return $this->model->where('created_at', '<', now()->subDays($days))->delete();
    }
}
