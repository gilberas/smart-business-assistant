<?php

namespace App\Services\AI;

use App\Adapters\AI\AnthropicAdapter;
use App\Adapters\AI\GeminiAdapter;
use App\Adapters\AI\OpenAIAdapter;
use App\Adapters\AI\OpenRouterAdapter;
use App\Contracts\AI\AIProviderInterface;
use App\Enums\AI\AIProvider;
use App\Models\AI\AIProvider as AIProviderModel;
use App\Repositories\AI\AIProviderRepository;
use InvalidArgumentException;

class AIProviderFactory
{
    private array $adapters = [];

    private ?AIProviderInterface $fallbackAdapter = null;

    public function __construct(
        private readonly AIProviderRepository $providerRepository,
    ) {}

    public function make(?AIProvider $provider = null): AIProviderInterface
    {
        $provider = $provider ?? AIProvider::OpenAI;

        if (isset($this->adapters[$provider->value])) {
            return $this->adapters[$provider->value];
        }

        $adapter = $this->createAdapter($provider);
        $this->configureFromDatabase($adapter, $provider);

        $this->adapters[$provider->value] = $adapter;
        return $adapter;
    }

    public function fromModel(AIProviderModel $providerModel): AIProviderInterface
    {
        $provider = AIProvider::tryFrom($providerModel->slug);
        if (!$provider) {
            throw new InvalidArgumentException("Unknown provider slug: {$providerModel->slug}");
        }

        $adapter = $this->createAdapter($provider);
        $adapter->configure([
            'api_key' => $providerModel->api_key ?? '',
            'base_url' => $providerModel->base_url ?? $provider->baseUrl(),
            'default_model' => $provider->defaultModel(),
            'timeout' => 30,
            'retries' => 2,
        ]);

        return $adapter;
    }

    public function default(): AIProviderInterface
    {
        $defaultProvider = $this->providerRepository->default();
        if ($defaultProvider) {
            return $this->fromModel($defaultProvider);
        }

        return $this->make(AIProvider::OpenAI);
    }

    public function fallback(): ?AIProviderInterface
    {
        if ($this->fallbackAdapter) {
            return $this->fallbackAdapter;
        }

        $fallbackProviders = $this->providerRepository->active()
            ->filter(fn($p) => !$p->is_default)
            ->sortBy('priority');

        foreach ($fallbackProviders as $providerModel) {
            try {
                $adapter = $this->fromModel($providerModel);
                if ($adapter->health()) {
                    $this->fallbackAdapter = $adapter;
                    return $adapter;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    public function available(): array
    {
        $providers = [];
        foreach (AIProvider::cases() as $provider) {
            try {
                $adapter = $this->make($provider);
                $providers[$provider->value] = [
                    'name' => $provider->label(),
                    'healthy' => $adapter->health(),
                    'models' => $adapter->listModels(),
                ];
            } catch (\Throwable $e) {
                $providers[$provider->value] = [
                    'name' => $provider->label(),
                    'healthy' => false,
                    'models' => [],
                ];
            }
        }
        return $providers;
    }

    public function forBusiness(?int $businessId = null): AIProviderInterface
    {
        if (!$businessId) {
            return $this->default();
        }

        $config = app(AIConfigurationService::class)->getForBusiness($businessId);
        if ($config && $config->provider) {
            $provider = AIProvider::tryFrom($config->provider);
            if ($provider) {
                $adapter = $this->make($provider);
                $adapter->configure([
                    'model' => $config->model,
                    'temperature' => $config->temperature,
                    'top_p' => $config->top_p,
                    'max_tokens' => $config->max_tokens,
                    'timeout' => $config->timeout_seconds,
                ]);
                return $adapter;
            }
        }

        return $this->default();
    }

    private function createAdapter(AIProvider $provider): AIProviderInterface
    {
        return match ($provider) {
            AIProvider::OpenAI => new OpenAIAdapter(),
            AIProvider::Anthropic => new AnthropicAdapter(),
            AIProvider::Google => new GeminiAdapter(),
            AIProvider::OpenRouter => new OpenRouterAdapter(),
        };
    }

    private function configureFromDatabase(AIProviderInterface $adapter, AIProvider $provider): void
    {
        $providerModel = $this->providerRepository->findBySlug($provider->value);
        if ($providerModel) {
            $adapter->configure([
                'api_key' => $providerModel->api_key ?? '',
                'base_url' => $providerModel->base_url ?? $provider->baseUrl(),
                'default_model' => $provider->defaultModel(),
                'timeout' => 30,
                'retries' => 2,
            ]);
        } else {
            $adapter->configure([
                'api_key' => config("ai.providers.{$provider->value}.api_key", ''),
                'base_url' => $provider->baseUrl(),
                'default_model' => $provider->defaultModel(),
                'timeout' => 30,
                'retries' => 2,
            ]);
        }
    }
}
