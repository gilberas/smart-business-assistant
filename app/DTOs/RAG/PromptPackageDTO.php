<?php

namespace App\DTOs\RAG;

readonly class PromptPackageDTO
{
    public function __construct(
        public string $systemInstruction,
        public string $businessContext,
        public string $retrievedContext,
        public string $conversationHistory,
        public string $userMessage,
        public string $outputFormatInstruction,
        public int $totalTokens = 0,
        public int $availableTokens = 0,
        public bool $wasTruncated = false,
        public ?ContextPackageDTO $contextPackage = null,
    ) {}

    public function toArray(): array
    {
        return [
            'system_instruction' => $this->systemInstruction,
            'business_context' => $this->businessContext,
            'retrieved_context' => $this->retrievedContext,
            'conversation_history' => $this->conversationHistory,
            'user_message' => $this->userMessage,
            'output_format_instruction' => $this->outputFormatInstruction,
            'total_tokens' => $this->totalTokens,
            'available_tokens' => $this->availableTokens,
            'was_truncated' => $this->wasTruncated,
        ];
    }

    public function assemble(): string
    {
        $parts = [];

        if ($this->systemInstruction) {
            $parts[] = $this->systemInstruction;
        }

        if ($this->businessContext) {
            $parts[] = "[BUSINESS CONTEXT]\n{$this->businessContext}";
        }

        if ($this->retrievedContext) {
            $parts[] = "[RETRIEVED INFORMATION]\n{$this->retrievedContext}";
        }

        if ($this->conversationHistory) {
            $parts[] = "[CONVERSATION HISTORY]\n{$this->conversationHistory}";
        }

        if ($this->outputFormatInstruction) {
            $parts[] = $this->outputFormatInstruction;
        }

        $parts[] = "[USER MESSAGE]\n{$this->userMessage}";

        return implode("\n\n", $parts);
    }
}
