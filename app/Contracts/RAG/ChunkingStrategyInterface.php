<?php

namespace App\Contracts\RAG;

use App\DTOs\RAG\ChunkDTO;

interface ChunkingStrategyInterface
{
    public function chunk(string $content, array $options = []): array;
    public function name(): string;
    public function supports(string $content, array $options = []): bool;
}
