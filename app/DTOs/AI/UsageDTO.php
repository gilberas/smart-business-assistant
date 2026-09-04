<?php

namespace App\DTOs\AI;

readonly class UsageDTO
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
        public float $estimatedCost = 0.0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: $data['prompt_tokens'] ?? 0,
            completionTokens: $data['completion_tokens'] ?? 0,
            totalTokens: ($data['prompt_tokens'] ?? 0) + ($data['completion_tokens'] ?? 0),
            estimatedCost: $data['estimated_cost'] ?? 0.0,
        );
    }

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'estimated_cost' => $this->estimatedCost,
        ];
    }
}
