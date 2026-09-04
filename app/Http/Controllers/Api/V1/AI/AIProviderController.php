<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StoreAIProviderRequest;
use App\Http\Requests\AI\UpdateAIProviderRequest;
use App\Http\Resources\AI\AIProviderResource;
use App\Models\AI\AIProvider;
use App\Repositories\AI\AIProviderRepository;
use App\Repositories\AI\AIModelRepository;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\HealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIProviderController extends Controller
{
    public function __construct(
        private readonly AIProviderRepository $providerRepository,
        private readonly AIModelRepository $modelRepository,
        private readonly AIProviderFactory $providerFactory,
        private readonly HealthService $healthService,
    ) {}

    public function index(): JsonResponse
    {
        $providers = $this->providerRepository->all();
        return response()->json([
            'data' => AIProviderResource::collection($providers),
        ]);
    }

    public function store(StoreAIProviderRequest $request): JsonResponse
    {
        $provider = $this->providerRepository->create($request->validated());
        return response()->json([
            'data' => new AIProviderResource($provider->load('models')),
            'message' => __('ai.provider_created'),
        ], 201);
    }

    public function show(AIProvider $aiProvider): JsonResponse
    {
        return response()->json([
            'data' => new AIProviderResource($aiProvider->load('models')),
        ]);
    }

    public function update(UpdateAIProviderRequest $request, AIProvider $aiProvider): JsonResponse
    {
        $this->providerRepository->update($aiProvider, $request->validated());
        return response()->json([
            'data' => new AIProviderResource($aiProvider->fresh()->load('models')),
            'message' => __('ai.provider_updated'),
        ]);
    }

    public function destroy(AIProvider $aiProvider): JsonResponse
    {
        $this->providerRepository->delete($aiProvider);
        return response()->json(['message' => __('ai.provider_deleted')]);
    }

    public function syncModels(AIProvider $aiProvider): JsonResponse
    {
        try {
            $adapter = $this->providerFactory->fromModel($aiProvider);
            $remoteModels = $adapter->listModels();

            $this->modelRepository->deactivateByProvider($aiProvider->id);

            $count = 0;
            foreach ($remoteModels as $modelData) {
                $this->modelRepository->updateOrCreate(
                    [
                        'ai_provider_id' => $aiProvider->id,
                        'model_id' => $modelData['id'],
                    ],
                    [
                        'name' => $modelData['name'] ?? $modelData['id'],
                        'is_active' => true,
                    ]
                );
                $count++;
            }

            return response()->json([
                'message' => __('ai.models_synced', ['count' => $count]),
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => __('ai.sync_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function health(AIProvider $aiProvider): JsonResponse
    {
        $result = $this->healthService->checkProvider($aiProvider->id);
        return response()->json($result);
    }

    public function healthHistory(AIProvider $aiProvider, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $history = $this->healthService->history($aiProvider->id, $limit);
        return response()->json(['data' => $history]);
    }

    public function available(Request $request): JsonResponse
    {
        $providers = $this->providerFactory->available();
        return response()->json(['data' => $providers]);
    }
}
