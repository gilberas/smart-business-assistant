<?php

namespace App\Contracts\RAG;

use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\PromptPackageDTO;

interface PromptBuilderInterface
{
    public function build(ContextPackageDTO $context, string $userMessage, array $options = []): PromptPackageDTO;
    public function name(): string;
}
