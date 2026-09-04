<?php

namespace App\DTOs\AI;

use App\Enums\AI\AIRequestStatus;

readonly class AIResponseDTO
{
    public function __construct(
        public string $content,
        public ?string $finishReason = null,
        public ?UsageDTO $usage = null,
        public ?float $latencyMs = null,
        public AIRequestStatus $status = AIRequestStatus::Completed,
        public ?string $error = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?array $rawResponse = null,
    ) {}

    public static function success(string $content, ?UsageDTO $usage = null, ?float $latencyMs = null, ?string $provider = null, ?string $model = null, ?string $finishReason = null, ?array $rawResponse = null): self
    {
        return new self(
            content: $content,
            finishReason: $finishReason,
            usage: $usage,
            latencyMs: $latencyMs,
            status: AIRequestStatus::Completed,
            provider: $provider,
            model: $model,
            rawResponse: $rawResponse,
        );
    }

    public static function error(string $error, ?string $provider = null, ?string $model = null): self
    {
        return new self(
            content: '',
            error: $error,
            status: AIRequestStatus::Failed,
            provider: $provider,
            model: $model,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === AIRequestStatus::Completed;
    }

    public function isError(): bool
    {
        return $this->status === AIRequestStatus::Failed;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'finish_reason' => $this->finishReason,
            'usage' => $this->usage?->toArray(),
            'latency_ms' => $this->latencyMs,
            'status' => $this->status->value,
            'error' => $this->error,
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }
}
