<?php

namespace App\Services\AI;

use App\Models\AI\ProviderHealth;
use App\Repositories\AI\AIProviderRepository;
use App\Repositories\AI\ProviderHealthRepository;

class HealthService
{
    public function __construct(
        private readonly AIProviderRepository $providerRepository,
        private readonly ProviderHealthRepository $healthRepository,
        private readonly AIProviderFactory $providerFactory,
    ) {}

    public function checkAll(): array
    {
        $results = [];
        $providers = $this->providerRepository->active();

        foreach ($providers as $provider) {
            $results[$provider->slug] = $this->checkProvider($provider->id);
        }

        return $results;
    }

    public function checkProvider(int $providerId): array
    {
        $provider = $this->providerRepository->findById($providerId);
        if (!$provider) {
            return ['error' => 'Provider not found'];
        }

        try {
            $adapter = $this->providerFactory->fromModel($provider);
            $start = microtime(true);
            $healthy = $adapter->health();
            $latency = (microtime(true) - $start) * 1000;

            $this->healthRepository->create([
                'ai_provider_id' => $providerId,
                'is_healthy' => $healthy,
                'latency_ms' => (int) $latency,
                'status_code' => $healthy ? '200' : '500',
                'checked_at' => now(),
            ]);

            return [
                'healthy' => $healthy,
                'latency_ms' => (int) $latency,
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $this->healthRepository->create([
                'ai_provider_id' => $providerId,
                'is_healthy' => false,
                'error_message' => $e->getMessage(),
                'checked_at' => now(),
            ]);

            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    public function latest(int $providerId): ?ProviderHealth
    {
        return $this->healthRepository->latestByProvider($providerId);
    }

    public function history(int $providerId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->healthRepository->historyByProvider($providerId, $limit);
    }
}
