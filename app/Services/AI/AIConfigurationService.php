<?php

namespace App\Services\AI;

use App\Models\AI\AIConfiguration;
use App\Repositories\AI\AIConfigurationRepository;

class AIConfigurationService
{
    public function __construct(
        private readonly AIConfigurationRepository $repository,
    ) {}

    public function getForBusiness(int $businessId): ?AIConfiguration
    {
        return $this->repository->findByBusiness($businessId);
    }

    public function updateOrCreate(int $businessId, array $data): AIConfiguration
    {
        $allowed = array_intersect_key($data, array_flip([
            'provider', 'model', 'temperature', 'top_p', 'max_tokens',
            'timeout_seconds', 'streaming', 'language', 'safety_settings', 'system_prompt',
            'is_active',
        ]));

        return $this->repository->updateOrCreate($businessId, $allowed);
    }

    public function resolve(int $businessId, array $overrides = []): array
    {
        $config = $this->getForBusiness($businessId);

        return [
            'provider' => $overrides['provider'] ?? $config?->provider ?? config('ai.defaults.provider', 'openai'),
            'model' => $overrides['model'] ?? $config?->model ?? config('ai.defaults.model', 'gpt-4o'),
            'temperature' => $overrides['temperature'] ?? $config?->temperature ?? config('ai.defaults.temperature', 0.7),
            'top_p' => $overrides['top_p'] ?? $config?->top_p ?? config('ai.defaults.top_p', 1.0),
            'max_tokens' => $overrides['max_tokens'] ?? $config?->max_tokens ?? config('ai.defaults.max_tokens', 2048),
            'timeout_seconds' => $config?->timeout_seconds ?? config('ai.defaults.timeout', 30),
            'streaming' => $overrides['streaming'] ?? $config?->streaming ?? false,
            'language' => $config?->language ?? 'en',
            'safety_settings' => $config?->safety_settings,
            'system_prompt' => $config?->system_prompt,
            'is_active' => $config?->is_active ?? true,
        ];
    }
}
