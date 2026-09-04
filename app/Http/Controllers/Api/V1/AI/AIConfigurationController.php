<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\UpdateAIConfigurationRequest;
use App\Http\Resources\AI\AIConfigurationResource;
use App\Services\AI\AIConfigurationService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class AIConfigurationController extends Controller
{
    public function __construct(
        private readonly AIConfigurationService $configurationService,
        private readonly TenantService $tenantService,
    ) {}

    private function business(): \App\Models\Business
    {
        return $this->tenantService->currentOrFail();
    }

    public function show(): JsonResponse
    {
        $business = $this->business();
        $config = $this->configurationService->getForBusiness($business->id);

        if (!$config) {
            return response()->json([
                'data' => [
                    'provider' => config('ai.defaults.provider', 'openai'),
                    'model' => config('ai.defaults.model', 'gpt-4o'),
                    'temperature' => config('ai.defaults.temperature', 0.7),
                    'top_p' => config('ai.defaults.top_p', 1.0),
                    'max_tokens' => config('ai.defaults.max_tokens', 2048),
                    'timeout_seconds' => config('ai.defaults.timeout', 30),
                    'streaming' => false,
                    'language' => 'en',
                    'is_active' => config('ai.defaults.is_active', true),
                ],
            ]);
        }

        return response()->json([
            'data' => new AIConfigurationResource($config),
        ]);
    }

    public function update(UpdateAIConfigurationRequest $request): JsonResponse
    {
        $business = $this->business();
        $config = $this->configurationService->updateOrCreate($business->id, $request->validated());

        return response()->json([
            'data' => new AIConfigurationResource($config),
            'message' => __('ai.configuration_updated'),
        ]);
    }

    public function resolved(): JsonResponse
    {
        $business = $this->business();
        $resolved = $this->configurationService->resolve($business->id);

        return response()->json(['data' => $resolved]);
    }
}
