<?php

namespace App\Contracts\AI;

use App\Models\PromptTemplate;

interface PromptServiceInterface
{
    public function render(int $templateId, array $variables = []): string;

    public function validate(int $templateId, array $variables = []): array;

    public function compile(string $template, array $variables): string;

    public function getVariables(string $content): array;

    public function createVersion(PromptTemplate $template, string $content, string $changelog = ''): void;
}
