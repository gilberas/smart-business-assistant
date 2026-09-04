<?php

namespace App\Contracts\AI;

use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\MessageDTO;

interface AIProviderInterface
{
    public function generateText(array $messages, array $options = []): AIResponseDTO;

    public function generateStructuredOutput(array $messages, array $schema, array $options = []): AIResponseDTO;

    public function streamResponse(array $messages, callable $onChunk, array $options = []): AIResponseDTO;

    public function countTokens(array $messages): int;

    public function listModels(): array;

    public function health(): bool;

    public function name(): string;

    public function configure(array $config): void;
}
