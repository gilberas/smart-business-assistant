<?php

namespace App\Services\AI;

use App\Contracts\AI\AIServiceInterface;
use App\DTOs\AI\AIRequestDTO;
use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\MessageDTO;
use App\Enums\AI\AIRequestStatus;
use App\Events\AI\AIRequestCompleted;
use App\Events\AI\AIRequestFailed;
use App\Events\AI\AIRequestStarted;
use App\Models\AI\AIRequest as AIRequestModel;
use App\Repositories\AI\AIRequestRepository;
use App\Repositories\AI\AIUsageRepository;
use Illuminate\Support\Facades\DB;

class AIService implements AIServiceInterface
{
    public function __construct(
        private readonly AIProviderFactory $providerFactory,
        private readonly AIRequestRepository $requestRepository,
        private readonly AIUsageRepository $usageRepository,
        private readonly AIConfigurationService $configurationService,
        private readonly TokenService $tokenService,
    ) {}

    public function generateText(AIRequestDTO $request): AIResponseDTO
    {
        $provider = $this->providerFactory->forBusiness($request->businessId);
        $adapter = $provider;

        $aiRequest = $this->beginRequest($request, $adapter->name());

        try {
            $options = $this->buildOptions($request, $adapter);

            $response = $adapter->generateText(
                messages: $request->messages,
                options: $options,
            );

            if ($response->isError()) {
                $fallback = $this->tryFallback($request, $adapter);
                if ($fallback) {
                    $this->completeRequest($aiRequest, $fallback, $options);
                    return $fallback;
                }
            }

            $this->completeRequest($aiRequest, $response, $options);
            return $response;
        } catch (\Throwable $e) {
            $fallback = $this->tryFallback($request, $adapter);
            if ($fallback) {
                $this->completeRequest($aiRequest, $fallback, []);
                return $fallback;
            }

            $errorResponse = AIResponseDTO::error($e->getMessage(), $adapter->name());
            $this->failRequest($aiRequest, $e);
            return $errorResponse;
        }
    }

    public function generateStructuredOutput(AIRequestDTO $request, array $schema): AIResponseDTO
    {
        $provider = $this->providerFactory->forBusiness($request->businessId);
        $adapter = $provider;

        $aiRequest = $this->beginRequest($request, $adapter->name());

        try {
            $options = $this->buildOptions($request, $adapter);

            $response = $adapter->generateStructuredOutput(
                messages: $request->messages,
                schema: $schema,
                options: $options,
            );

            if ($response->isError()) {
                $fallback = $this->tryFallback($request, $adapter);
                if ($fallback && $fallback->isSuccess()) {
                    $this->completeRequest($aiRequest, $fallback, $options);
                    return $fallback;
                }
            }

            $this->completeRequest($aiRequest, $response, $options);
            return $response;
        } catch (\Throwable $e) {
            $fallback = $this->tryFallback($request, $adapter);
            if ($fallback && $fallback->isSuccess()) {
                $this->completeRequest($aiRequest, $fallback, []);
                return $fallback;
            }

            $errorResponse = AIResponseDTO::error($e->getMessage(), $adapter->name());
            $this->failRequest($aiRequest, $e);
            return $errorResponse;
        }
    }

    public function streamResponse(AIRequestDTO $request, callable $onChunk): AIResponseDTO
    {
        $provider = $this->providerFactory->forBusiness($request->businessId);
        $adapter = $provider;

        $aiRequest = $this->beginRequest($request, $adapter->name());

        try {
            $options = $this->buildOptions($request, $adapter);
            $options['stream'] = true;

            $response = $adapter->streamResponse(
                messages: $request->messages,
                onChunk: $onChunk,
                options: $options,
            );

            $this->completeRequest($aiRequest, $response, $options);
            return $response;
        } catch (\Throwable $e) {
            $errorResponse = AIResponseDTO::error($e->getMessage(), $adapter->name());
            $this->failRequest($aiRequest, $e);
            return $errorResponse;
        }
    }

    public function countTokens(AIRequestDTO $request): int
    {
        return $this->tokenService->count($request->messages, $request->model);
    }

    public function health(): array
    {
        $health = [];
        $providers = $this->providerFactory->available();

        foreach ($providers as $slug => $info) {
            $health[$slug] = [
                'healthy' => $info['healthy'],
                'name' => $info['name'],
            ];
        }

        return $health;
    }

    public function listModels(?string $provider = null): array
    {
        if ($provider) {
            $providerEnum = \App\Enums\AI\AIProvider::tryFrom($provider);
            if (!$providerEnum) {
                return [];
            }
            $adapter = $this->providerFactory->make($providerEnum);
            return $adapter->listModels();
        }

        $allModels = [];
        foreach (\App\Enums\AI\AIProvider::cases() as $providerEnum) {
            try {
                $adapter = $this->providerFactory->make($providerEnum);
                $allModels[$providerEnum->value] = $adapter->listModels();
            } catch (\Throwable) {
                $allModels[$providerEnum->value] = [];
            }
        }

        return $allModels;
    }

    private function beginRequest(AIRequestDTO $request, string $providerName): AIRequestModel
    {
        $model = $request->model ?? 'unknown';

        $aiRequest = $this->requestRepository->create([
            'business_id' => $request->businessId,
            'user_id' => $request->userId,
            'conversation_id' => $request->conversationId,
            'provider' => $providerName,
            'model' => $model,
            'status' => AIRequestStatus::Processing->value,
            'request_data' => $request->toArray(),
            'metadata' => $request->metadata,
        ]);

        event(new AIRequestStarted($aiRequest));

        return $aiRequest;
    }

    private function completeRequest(AIRequestModel $aiRequest, AIResponseDTO $response, array $options): void
    {
        $this->requestRepository->update($aiRequest, [
            'status' => $response->status->value,
            'finish_reason' => $response->finishReason,
            'prompt_tokens' => $response->usage?->promptTokens ?? 0,
            'completion_tokens' => $response->usage?->completionTokens ?? 0,
            'total_tokens' => $response->usage?->totalTokens ?? 0,
            'estimated_cost' => $response->usage?->estimatedCost ?? 0,
            'duration_ms' => (int) $response->latencyMs,
            'response_data' => $response->rawResponse,
        ]);

        $this->trackUsage($aiRequest, $response);

        event(new AIRequestCompleted($aiRequest, $response));
    }

    private function failRequest(AIRequestModel $aiRequest, \Throwable $e): void
    {
        $this->requestRepository->update($aiRequest, [
            'status' => AIRequestStatus::Failed->value,
            'error_message' => $e->getMessage(),
        ]);

        event(new AIRequestFailed($aiRequest, $e));
    }

    private function trackUsage(AIRequestModel $aiRequest, AIResponseDTO $response): void
    {
        if (!$aiRequest->business_id) {
            return;
        }

        $usage = $response->usage;
        $this->usageRepository->upsertDaily([
            'business_id' => $aiRequest->business_id,
            'user_id' => $aiRequest->user_id,
            'provider' => $aiRequest->provider,
            'model' => $aiRequest->model,
            'date' => now()->toDateString(),
            'total_requests' => 1,
            'successful_requests' => $response->isSuccess() ? 1 : 0,
            'failed_requests' => $response->isError() ? 1 : 0,
            'prompt_tokens' => $usage?->promptTokens ?? 0,
            'completion_tokens' => $usage?->completionTokens ?? 0,
            'total_tokens' => $usage?->totalTokens ?? 0,
            'estimated_cost' => $usage?->estimatedCost ?? 0,
            'total_duration_ms' => (int) ($response->latencyMs ?? 0),
        ]);
    }

    private function buildOptions(AIRequestDTO $request, $adapter): array
    {
        return array_filter([
            'model' => $request->model,
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'max_tokens' => $request->maxTokens,
            'stream' => $request->stream,
        ], fn($v) => $v !== null);
    }

    private function tryFallback(AIRequestDTO $request, $failedAdapter): ?AIResponseDTO
    {
        try {
            $fallbackAdapter = $this->providerFactory->fallback();
            if (!$fallbackAdapter || $fallbackAdapter->name() === $failedAdapter->name()) {
                return null;
            }

            return $fallbackAdapter->generateText(
                messages: $request->messages,
                options: ['model' => $request->model],
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
