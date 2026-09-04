<?php

namespace App\Jobs\AI;

use App\DTOs\AI\AIRequestDTO;
use App\DTOs\AI\MessageDTO;
use App\Services\AI\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAIRequest implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public array $messages,
        public array $options = [],
        public ?int $businessId = null,
        public ?int $userId = null,
        public ?int $conversationId = null,
    ) {}

    public function handle(AIService $aiService): void
    {
        $messageDTOs = [];
        foreach ($this->messages as $message) {
            $messageDTOs[] = new MessageDTO(
                role: $message['role'] ?? 'user',
                content: $message['content'] ?? '',
                name: $message['name'] ?? null,
            );
        }

        $request = new AIRequestDTO(
            messages: $messageDTOs,
            model: $this->options['model'] ?? null,
            temperature: $this->options['temperature'] ?? null,
            topP: $this->options['top_p'] ?? null,
            maxTokens: $this->options['max_tokens'] ?? null,
            businessId: $this->businessId,
            userId: $this->userId,
            conversationId: $this->conversationId,
        );

        $aiService->generateText($request);
    }
}
