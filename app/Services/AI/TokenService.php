<?php

namespace App\Services\AI;

class TokenService
{
    private array $pricing = [];

    public function __construct()
    {
        $this->loadPricing();
    }

    public function count(array $messages, ?string $model = null): int
    {
        $total = 0;
        foreach ($messages as $message) {
            $content = is_array($message) ? ($message['content'] ?? '') : (string) $message;
            $total += $this->countText($content);
        }
        return $total;
    }

    public function countText(string $text): int
    {
        if (empty($text)) {
            return 0;
        }
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function estimateCost(int $promptTokens, int $completionTokens, string $model = 'gpt-4o'): float
    {
        $pricing = $this->getModelPricing($model);
        if (!$pricing) {
            return 0.0;
        }

        $inputCost = ($promptTokens / 1000) * $pricing['input'];
        $outputCost = ($completionTokens / 1000) * $pricing['output'];

        return round($inputCost + $outputCost, 8);
    }

    public function getModelPricing(string $modelId): ?array
    {
        return $this->pricing[$modelId] ?? $this->pricing['default'] ?? null;
    }

    public function allPricing(): array
    {
        return $this->pricing;
    }

    public function refreshPricing(): void
    {
        $this->loadPricing();
    }

    private function loadPricing(): void
    {
        $this->pricing = config('ai.pricing', [
            'default' => ['input' => 0.0, 'output' => 0.0],
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'claude-3-5-sonnet-20241022' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-5-haiku-20241022' => ['input' => 0.00025, 'output' => 0.00125],
            'claude-3-opus-20240229' => ['input' => 0.015, 'output' => 0.075],
            'gemini-2.0-flash' => ['input' => 0.0001, 'output' => 0.0004],
            'gemini-2.0-pro' => ['input' => 0.001, 'output' => 0.002],
        ]);
    }
}
