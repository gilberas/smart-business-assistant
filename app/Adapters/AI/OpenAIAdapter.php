<?php

namespace App\Adapters\AI;

use App\DTOs\AI\AIResponseDTO;

class OpenAIAdapter extends BaseProviderAdapter
{
    protected string $baseUrl = 'https://api.openai.com/v1';

    protected string $defaultModel = 'gpt-4o';

    public function generateText(array $messages, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            [$response, $latency] = $this->measure(function () use ($messages, $model, $options) {
                return $this->client()->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $this->formatMessages($messages),
                    'temperature' => $options['temperature'] ?? 0.7,
                    'top_p' => $options['top_p'] ?? 1.0,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'stream' => false,
                ])->throw()->json();
            });

            $choice = $response['choices'][0] ?? [];
            $finishReason = $choice['finish_reason'] ?? null;
            $content = $choice['message']['content'] ?? '';

            return AIResponseDTO::success(
                content: $content,
                usage: $this->buildUsage($response),
                latencyMs: $latency,
                provider: 'openai',
                model: $model,
                finishReason: $finishReason,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('OpenAI', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'openai', $model);
        }
    }

    public function generateStructuredOutput(array $messages, array $schema, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            $payload = [
                'model' => $model,
                'messages' => $this->formatMessages($messages),
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2048,
                'stream' => false,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $options['schema_name'] ?? 'structured_output',
                        'schema' => $schema,
                        'strict' => $options['strict'] ?? true,
                    ],
                ],
            ];

            [$response, $latency] = $this->measure(function () use ($payload) {
                return $this->client()->post("{$this->baseUrl}/chat/completions", $payload)->throw()->json();
            });

            $content = $response['choices'][0]['message']['content'] ?? '';
            $finishReason = $response['choices'][0]['finish_reason'] ?? null;

            return AIResponseDTO::success(
                content: $content,
                usage: $this->buildUsage($response),
                latencyMs: $latency,
                provider: 'openai',
                model: $model,
                finishReason: $finishReason,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('OpenAI', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'openai', $model);
        }
    }

    public function streamResponse(array $messages, callable $onChunk, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;
        $fullContent = '';

        try {
            [$startTime] = $this->measure(function () {});

            $response = $this->client()->withOptions(['stream' => true])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $this->formatMessages($messages),
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'stream' => true,
                ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                $line = $body->readLine();
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    if ($data === '[DONE]') {
                        break;
                    }
                    $chunk = json_decode($data, true);
                    $delta = $chunk['choices'][0]['delta']['content'] ?? '';
                    if ($delta !== '') {
                        $fullContent .= $delta;
                        $onChunk($delta);
                    }
                }
            }

            $latency = (microtime(true) - $startTime) * 1000;

            return AIResponseDTO::success(
                content: $fullContent,
                latencyMs: $latency,
                provider: 'openai',
                model: $model,
                finishReason: 'stop',
            );
        } catch (\Throwable $e) {
            $this->logError('OpenAI', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'openai', $model);
        }
    }

    public function listModels(): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/models")->throw()->json();
            return array_map(fn($m) => [
                'id' => $m['id'],
                'name' => $m['id'],
                'owned_by' => $m['owned_by'] ?? 'openai',
            ], $response['data'] ?? []);
        } catch (\Throwable $e) {
            $this->logError('OpenAI', $e);
            return [];
        }
    }

    protected function mapMessages(array $messages): array
    {
        return $this->formatMessages($messages);
    }

    protected function parseResponse(array $response, array $options = []): AIResponseDTO
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        return AIResponseDTO::success($content, $this->buildUsage($response));
    }

    protected function parseError(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
