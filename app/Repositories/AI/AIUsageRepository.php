<?php

namespace App\Repositories\AI;

use App\Models\AI\AIUsage;
use Illuminate\Support\Collection;

class AIUsageRepository
{
    public function __construct(
        private readonly AIUsage $model,
    ) {}

    public function findByBusiness(int $businessId, array $filters = []): Collection
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function totalsByBusiness(int $businessId, array $filters = []): array
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return [
            'total_requests' => (int) $query->sum('total_requests'),
            'successful_requests' => (int) $query->sum('successful_requests'),
            'failed_requests' => (int) $query->sum('failed_requests'),
            'prompt_tokens' => (int) $query->sum('prompt_tokens'),
            'completion_tokens' => (int) $query->sum('completion_tokens'),
            'total_tokens' => (int) $query->sum('total_tokens'),
            'estimated_cost' => (float) $query->sum('estimated_cost'),
        ];
    }

    public function totalsByProvider(int $businessId, array $filters = []): Collection
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->groupBy('provider')
            ->selectRaw('provider, sum(total_requests) as total_requests, sum(successful_requests) as successful_requests, sum(failed_requests) as failed_requests, sum(total_tokens) as total_tokens, sum(estimated_cost) as estimated_cost')
            ->get();
    }

    public function upsertDaily(array $data): void
    {
        $this->model->updateOrCreate(
            [
                'business_id' => $data['business_id'],
                'provider' => $data['provider'],
                'model' => $data['model'],
                'date' => $data['date'],
            ],
            [
                'total_requests' => \Illuminate\Support\Facades\DB::raw('total_requests + ' . ($data['total_requests'] ?? 1)),
                'successful_requests' => \Illuminate\Support\Facades\DB::raw('successful_requests + ' . ($data['successful_requests'] ?? 0)),
                'failed_requests' => \Illuminate\Support\Facades\DB::raw('failed_requests + ' . ($data['failed_requests'] ?? 0)),
                'prompt_tokens' => \Illuminate\Support\Facades\DB::raw('prompt_tokens + ' . ($data['prompt_tokens'] ?? 0)),
                'completion_tokens' => \Illuminate\Support\Facades\DB::raw('completion_tokens + ' . ($data['completion_tokens'] ?? 0)),
                'total_tokens' => \Illuminate\Support\Facades\DB::raw('total_tokens + ' . ($data['total_tokens'] ?? 0)),
                'estimated_cost' => \Illuminate\Support\Facades\DB::raw('estimated_cost + ' . ($data['estimated_cost'] ?? 0)),
                'total_duration_ms' => \Illuminate\Support\Facades\DB::raw('total_duration_ms + ' . ($data['total_duration_ms'] ?? 0)),
            ]
        );
    }
}
