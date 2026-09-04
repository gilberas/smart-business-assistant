<?php

namespace App\Services\Conversation;

use App\Repositories\Conversation\ConversationEventRepository;

class ConversationEventService
{
    public function __construct(
        private readonly ConversationEventRepository $repository,
    ) {}

    public function getForConversation(int $conversationId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getForConversation($conversationId, $limit);
    }

    public function addEvent(int $conversationId, string $type, ?array $payload = null, ?int $createdBy = null): void
    {
        $this->repository->create([
            'conversation_id' => $conversationId,
            'type' => $type,
            'payload' => $payload,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }
}
