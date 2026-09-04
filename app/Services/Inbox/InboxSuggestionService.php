<?php

namespace App\Services\Inbox;

use App\DTOs\AI\AIRequestDTO;
use App\DTOs\AI\MessageDTO;
use App\Models\Conversation;
use App\Services\AI\AIService;
use App\Services\Conversation\MessageService;
use App\Services\RAG\RetrievalService;

class InboxSuggestionService
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly AIService $aiService,
        private readonly MessageService $messageService,
        private readonly InboxDecisionEngineService $decisionEngine,
    ) {}

    public function suggest(Conversation $conversation, int $businessId, array $options = []): array
    {
        $latestMessage = $this->latestInboundMessage($conversation->id);
        $queryText = $latestMessage['content'] ?? $conversation->title ?? '';

        $searchQuery = new \App\DTOs\RAG\SearchQueryDTO(
            query: $queryText,
            businessId: $businessId,
            customerId: $conversation->customer_id,
            conversationId: $conversation->id,
            maxResults: 5,
        );

        $context = $this->retrievalService->retrieveContext($searchQuery);
        $prompt = $this->retrievalService->buildPrompt(
            $searchQuery,
            $queryText,
            [
                'system_instruction' => 'You are an inbox copilot for a business support team. Draft a concise, accurate reply using only the provided context. Return a reply suitable for a human agent to review.',
                'output_format_instruction' => 'Return plain text only. Do not mention internal reasoning.',
            ],
        );

        $aiResponse = $this->aiService->generateText(new AIRequestDTO(
            messages: [
                MessageDTO::system($prompt->assemble()),
            ],
            businessId: $businessId,
            conversationId: $conversation->id,
            userId: $options['user_id'] ?? null,
            model: $options['model'] ?? null,
            temperature: 0.2,
        ));

        $suggestedReply = trim($aiResponse->content) !== ''
            ? trim($aiResponse->content)
            : $this->fallbackReply($conversation, $context->suggestedCitations);

        $decision = $this->decisionEngine->decide($context, $queryText, [
            'timezone' => $options['timezone'] ?? null,
            'rules' => $options['rules'] ?? [],
        ]);

        return [
            'decision' => $decision,
            'context' => $context->toArray(),
            'suggested_reply' => $suggestedReply,
            'alternative_replies' => $this->buildAlternatives($suggestedReply),
            'prompt' => $prompt->toArray() + ['assembled' => $prompt->assemble()],
            'ai_response' => $aiResponse->toArray(),
        ];
    }

    private function latestInboundMessage(int $conversationId): array
    {
        $messages = $this->messageService->getRecentForConversation($conversationId, 10);

        foreach ($messages as $message) {
            if (($message['direction'] ?? null) === 'inbound') {
                return $message;
            }
        }

        return $messages[0] ?? [];
    }

    private function fallbackReply(Conversation $conversation, array $citations): string
    {
        $source = $citations[0]['title'] ?? $conversation->title ?? 'the request';

        return "Thanks for reaching out. I’ve reviewed the available information about {$source}, and I’m checking the best next step for you.";
    }

    private function buildAlternatives(string $reply): array
    {
        $reply = trim($reply);

        return [
            ['label' => 'Short', 'content' => $this->shorten($reply)],
            ['label' => 'Professional', 'content' => $reply],
            ['label' => 'Friendly', 'content' => $this->makeFriendly($reply)],
            ['label' => 'Formal', 'content' => $this->makeFormal($reply)],
        ];
    }

    private function shorten(string $reply): string
    {
        $parts = preg_split('/(?<=[.!?])\s+/', $reply) ?: [$reply];

        return trim(implode(' ', array_slice($parts, 0, 2)));
    }

    private function makeFriendly(string $reply): string
    {
        return str_replace(['Thanks', 'Thank you'], 'Thanks', $reply) . ' Let me know if you want me to help further.';
    }

    private function makeFormal(string $reply): string
    {
        return str_replace(['Hi', 'Hello', 'Thanks'], ['Hello', 'Hello', 'Thank you'], $reply);
    }
}