<?php

namespace App\Adapters\AI;

use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\UsageDTO;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GeminiAdapter extends BaseProviderAdapter
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1';

    protected string $defaultModel = 'gemini-2.0-flash';

    public function generateText(array $messages, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            $contents = $this->buildGeminiContents($messages);

            [$response, $latency] = $this->measure(function () use ($contents, $model, $options) {
                return Http::withOptions(['timeout' => $this->timeout])
                    ->retry($this->retries, 500)
                    ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? 0.7,
                            'topP' => $options['top_p'] ?? 1.0,
                            'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                        ],
                    ])->throw()->json();
            });

            $candidate = $response['candidates'][0] ?? [];
            $content = $candidate['content']['parts'][0]['text'] ?? '';
            $finishReason = $candidate['finishReason'] ?? null;

            $promptTokens = $response['usageMetadata']['promptTokenCount'] ?? 0;
            $completionTokens = $response['usageMetadata']['candidatesTokenCount'] ?? 0;

            return AIResponseDTO::success(
                content: $content,
                usage: UsageDTO::fromArray([
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                ]),
                latencyMs: $latency,
                provider: 'google',
                model: $model,
                finishReason: $finishReason,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('Gemini', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'google', $model);
        }
    }

    public function generateStructuredOutput(array $messages, array $schema, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;

        try {
            $contents = $this->buildGeminiContents($messages);

            [$response, $latency] = $this->measure(function () use ($contents, $model, $options, $schema) {
                return Http::withOptions(['timeout' => $this->timeout])
                    ->retry($this->retries, 500)
                    ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? 0.7,
                            'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                            'responseMimeType' => 'application/json',
                            'responseSchema' => $schema,
                        ],
                    ])->throw()->json();
            });

            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return AIResponseDTO::success(
                content: $content,
                latencyMs: $latency,
                provider: 'google',
                model: $model,
                rawResponse: $response,
            );
        } catch (\Throwable $e) {
            $this->logError('Gemini', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'google', $model);
        }
    }

    public function streamResponse(array $messages, callable $onChunk, array $options = []): AIResponseDTO
    {
        $model = $options['model'] ?? $this->defaultModel;
        $fullContent = '';

        try {
            $startTime = microtime(true);
            $contents = $this->buildGeminiContents($messages);

            $response = Http::withOptions(['timeout' => $this->timeout, 'stream' => true])
                ->post("{$this->baseUrl}/models/{$model}:streamGenerateContent?key={$this->apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $options['temperature'] ?? 0.7,
                        'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                    ],
                ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                $line = $body->readLine();
                if (str_starts_with($line, 'data: ')) {
                    $data = json_decode(substr($line, 6), true);
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($text !== '') {
                        $fullContent .= $text;
                        $onChunk($text);
                    }
                }
            }

            $latency = (microtime(true) - $startTime) * 1000;

            return AIResponseDTO::success(
                content: $fullContent,
                latencyMs: $latency,
                provider: 'google',
                model: $model,
            );
        } catch (\Throwable $e) {
            $this->logError('Gemini', $e, ['model' => $model]);
            return AIResponseDTO::error($this->parseError($e), 'google', $model);
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/models?key={$this->apiKey}")->throw()->json();
            return array_map(fn($m) => [
                'id' => $m['name'],
                'name' => $m['displayName'] ?? $m['name'],
                'owned_by' => 'google',
            ], $response['models'] ?? []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function health(): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/models?key={$this->apiKey}");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function client(): PendingRequest
    {
        return Http::withOptions(['timeout' => $this->timeout])->retry($this->retries, 500);
    }

    private function buildGeminiContents(array $messages): array
    {
        $contents = [];
        foreach ($messages as $message) {
            $role = is_array($message) ? ($message['role'] ?? 'user') : 'user';
            $content = is_array($message) ? ($message['content'] ?? '') : (string) $message;

            $geminiRole = match ($role) {
                'assistant' => 'model',
                default => 'user',
            };

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]],
            ];
        }
        return $contents;
    }

    protected function mapMessages(array $messages): array
    {
        return $this->buildGeminiContents($messages);
    }

    protected function parseResponse(array $response, array $options = []): AIResponseDTO
    {
        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return AIResponseDTO::success($content);
    }

    protected function parseError(\Throwable $e): string
    {
        return $e->getMessage();
    }
}
