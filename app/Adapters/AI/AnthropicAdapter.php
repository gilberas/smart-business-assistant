<?php

namespace App\Adapters\AI;

use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\UsageDTO;

class AnthropicAdapter extends BaseProviderAdapter
{
    protected string $baseUrl = 'https://api.anthropic.com/v1';

    protected string $defaultModel = 'claude-3-5-sonnet-20241022';

    public function generateText(array $messages, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            [$response, $latency] = $this->measure(function () use ($messages, $model, $options) {
                return $this->client()->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->post("{$this->baseUrl}/messages", [
                    'model' => $model,
                    'messages' => $this->formatMessages($messages),
                    'system' => $options['system'] ?? null,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'top_p' => $options['top_p'] ?? 1.0,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'stream' => false,
                ])->throw()->json();
            });

            $content = '';
            foreach ($response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $content .= $block['text'] ?? '';
                }
            }

            return AIResponseDTO::success(
                content: $content,
                usage: UsageDTO::fromArray([
                    'prompt_tokens' => $response['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $response['usage']['output_tokens'] ?? 0,
                ]),
                latencyMs: $latency,
                provider: 'anthropic',
                model: $model,
                finishReason: $response['stop_reason'] ?? null,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('Anthropic', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'anthropic', $model);
        }
    }

    public function generateStructuredOutput(array $messages, array $schema, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            $systemPrompt = $options['system'] ?? '';
            $systemPrompt .= "\n\nYou must respond with valid JSON following this schema:\n" . json_encode($schema, JSON_PRETTY_PRINT);

            [$response, $latency] = $this->measure(function () use ($messages, $model, $options, $systemPrompt) {
                return $this->client()->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->post("{$this->baseUrl}/messages", [
                    'model' => $model,
                    'messages' => $this->formatMessages($messages),
                    'system' => $systemPrompt,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'stream' => false,
                ])->throw()->json();
            });

            $content = '';
            foreach ($response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $content .= $block['text'] ?? '';
                }
            }

            return AIResponseDTO::success(
                content: $content,
                usage: UsageDTO::fromArray([
                    'prompt_tokens' => $response['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $response['usage']['output_tokens'] ?? 0,
                ]),
                latencyMs: $latency,
                provider: 'anthropic',
                model: $model,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('Anthropic', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'anthropic', $model);
        }
    }

    public function streamResponse(array $messages, callable $onChunk, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;
        $fullContent = '';

        try {
            $startTime = microtime(true);

            $response = $this->client()->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])->withOptions(['stream' => true])->post("{$this->baseUrl}/messages", [
                'model' => $model,
                'messages' => $this->formatMessages($messages),
                'max_tokens' => $options['max_tokens'] ?? 2048,
                'stream' => true,
            ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                $line = $body->readLine();
                if (str_starts_with($line, 'data: ')) {
                    $data = json_decode(substr($line, 6), true);
                    if (isset($data['type']) && $data['type'] === 'content_block_delta') {
                        $text = $data['delta']['text'] ?? '';
                        if ($text !== '') {
                            $fullContent .= $text;
                            $onChunk($text);
                        }
                    }
                }
            }

            $latency = (microtime(true) - $startTime) * 1000;

            return AIResponseDTO::success(
                content: $fullContent,
                latencyMs: $latency,
                provider: 'anthropic',
                model: $model,
            );
        } catch (\Throwable $e) {
            $this->logError('Anthropic', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'anthropic', $model);
        }
    }

    public function listModels(): array
    {
        return [
            ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet', 'owned_by' => 'anthropic'],
            ['id' => 'claude-3-5-haiku-20241022', 'name' => 'Claude 3.5 Haiku', 'owned_by' => 'anthropic'],
            ['id' => 'claude-3-opus-20240229', 'name' => 'Claude 3 Opus', 'owned_by' => 'anthropic'],
            ['id' => 'claude-3-sonnet-20240229', 'name' => 'Claude 3 Sonnet', 'owned_by' => 'anthropic'],
        ];
    }

    protected function mapMessages(array $messages): array
    {
        return $this->formatMessages($messages);
    }

    protected function parseResponse(array $response, array $options = []): AIResponseDTO
    {
        $content = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $content .= $block['text'] ?? '';
            }
        }
        return AIResponseDTO::success($content);
    }

    protected function parseError(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
