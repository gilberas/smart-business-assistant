<?php

namespace App\Services\AI;

use App\Repositories\AI\AIUsageRepository;

class UsageService
{
    public function __construct(
        private readonly AIUsageRepository $usageRepository,
    ) {}

    public function getForBusiness(int $businessId, array $filters = []): array
    {
        $records = $this->usageRepository->findByBusiness($businessId, $filters);
        $totals = $this->usageRepository->totalsByBusiness($businessId, $filters);
        $byProvider = $this->usageRepository->totalsByProvider($businessId, $filters);

        return [
            'records' => $records,
            'totals' => $totals,
            'by_provider' => $byProvider,
        ];
    }

    public function getTotals(int $businessId, array $filters = []): array
    {
        return $this->usageRepository->totalsByBusiness($businessId, $filters);
    }

    public function getByProvider(int $businessId, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->usageRepository->totalsByProvider($businessId, $filters);
    }

    public function getDaily(int $businessId, string $dateFrom, string $dateTo): \Illuminate\Database\Eloquent\Collection
    {
        return $this->usageRepository->findByBusiness($businessId, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }
}
