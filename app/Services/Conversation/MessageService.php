<?php

namespace App\Services\Conversation;

use App\Events\Conversation\MessageReceived;
use App\Events\Conversation\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\Conversation\MessageRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $repository,
        private readonly ConversationRepository $conversationRepository,
    ) {}

    public function getForConversation(int $conversationId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->getForConversation($conversationId, $perPage);
    }

    public function getRecentForConversation(int $conversationId, int $limit = 50): array
    {
        return $this->repository->getRecentForConversation($conversationId, $limit);
    }

    public function find(int $id): ?Message
    {
        return $this->repository->findById($id);
    }

    public function send(int $conversationId, array $data, ?Conversation &$conversation = null): Message
    {
        $message = $this->repository->createForConversation($conversationId, $data);

        $conversation = $this->conversationRepository->findById($conversationId);
        if ($conversation) {
            $conversation->touchLastMessage($message);
        }

        event(new MessageSent($message, $conversation));

        return $message;
    }

    public function receive(int $conversationId, array $data, ?Conversation &$conversation = null): Message
    {
        $message = $this->repository->createForConversation($conversationId, $data);

        $conversation = $this->conversationRepository->findById($conversationId);
        if ($conversation) {
            $conversation->touchLastMessage($message);
        }

        event(new MessageReceived($message, $conversation));

        return $message;
    }

    public function createInConversation(int $conversationId, array $data): Message
    {
        $message = $this->repository->createForConversation($conversationId, $data);

        $conversation = $this->conversationRepository->findById($conversationId);
        if ($conversation) {
            $conversation->touchLastMessage($message);
        }

        return $message;
    }
}
