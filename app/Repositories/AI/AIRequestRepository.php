<?php

namespace App\Repositories\AI;

use App\Models\AI\AIRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class AIRequestRepository
{
    public function __construct(
        private readonly AIRequest $model,
    ) {}

    public function paginate(int $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }
        if (!empty($filters['model'])) {
            $query->where('model', $filters['model']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?AIRequest
    {
        return $this->model->find($id);
    }

    public function create(array $data): AIRequest
    {
        return $this->model->create($data);
    }

    public function update(AIRequest $request, array $data): bool
    {
        return $request->update($data);
    }
}
