<?php

namespace App\Adapters\AI;

use App\Contracts\AI\AIProviderInterface;
use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\UsageDTO;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseProviderAdapter implements AIProviderInterface
{
    protected string $apiKey = '';

    protected string $baseUrl = '';

    protected string $defaultModel = '';

    protected int $timeout = 30;

    protected int $retries = 2;

    abstract protected function mapMessages(array $messages): array;

    abstract protected function parseResponse(array $response, array $options = []): AIResponseDTO;

    abstract protected function parseError(\Throwable $e): string;

    protected function client(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retries, 500);
    }

    public function configure(array $config): void
    {
        $this->apiKey = $config['api_key'] ?? $this->apiKey;
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->defaultModel = $config['default_model'] ?? $this->defaultModel;
        $this->timeout = $config['timeout'] ?? $this->timeout;
        $this->retries = $config['retries'] ?? $this->retries;
    }

    public function countTokens(array $messages): int
    {
        $text = '';
        foreach ($messages as $message) {
            $content = is_array($message) ? ($message['content'] ?? '') : (string) $message;
            $text .= $content;
        }
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function name(): string
    {
        return class_basename(static::class);
    }

    protected function formatMessages(array $messages): array
    {
        return array_map(function ($message) {
            if ($message instanceof \App\DTOs\AI\MessageDTO) {
                return $message->toArray();
            }
            return $message;
        }, $messages);
    }

    protected function measure(callable $fn): array
    {
        $start = microtime(true);
        $result = $fn();
        $latency = (microtime(true) - $start) * 1000;
        return [$result, $latency];
    }

    protected function logError(string $provider, \Throwable $e, array $context = []): void
    {
        Log::error("AI Provider {$provider} Error: {$e->getMessage()}", $context);
    }

    protected function buildUsage(array $response): UsageDTO
    {
        return UsageDTO::fromArray([
            'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
            'estimated_cost' => $response['usage']['estimated_cost'] ?? 0,
        ]);
    }

    public function health(): bool
    {
        try {
            $response = $this->client()->get(rtrim($this->baseUrl, '/') . '/models');
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
