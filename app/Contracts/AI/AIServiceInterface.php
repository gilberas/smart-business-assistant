<?php

namespace App\Contracts\AI;

use App\DTOs\AI\AIRequestDTO;
use App\DTOs\AI\AIResponseDTO;

interface AIServiceInterface
{
    public function generateText(AIRequestDTO $request): AIResponseDTO;

    public function generateStructuredOutput(AIRequestDTO $request, array $schema): AIResponseDTO;

    public function streamResponse(AIRequestDTO $request, callable $onChunk): AIResponseDTO;

    public function countTokens(AIRequestDTO $request): int;

    public function health(): array;

    public function listModels(?string $provider = null): array;
}
