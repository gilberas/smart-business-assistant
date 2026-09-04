<?php

namespace App\DTOs\AI;

use App\Enums\AI\AIProvider;

readonly class AIRequestDTO
{
    public function __construct(
        public array $messages,
        public ?AIProvider $provider = null,
        public ?string $model = null,
        public ?float $temperature = null,
        public ?float $topP = null,
        public ?int $maxTokens = null,
        public bool $stream = false,
        public ?array $structuredOutput = null,
        public ?int $businessId = null,
        public ?int $userId = null,
        public ?int $conversationId = null,
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        return [
            'messages' => array_map(fn(MessageDTO $m) => $m->toArray(), $this->messages),
            'provider' => $this->provider?->value,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'max_tokens' => $this->maxTokens,
            'stream' => $this->stream,
            'structured_output' => $this->structuredOutput,
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
        ];
    }
}
