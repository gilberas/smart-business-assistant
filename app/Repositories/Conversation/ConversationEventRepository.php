<?php

namespace App\Repositories\Conversation;

use App\Models\ConversationEvent;
use Illuminate\Database\Eloquent\Collection;

class ConversationEventRepository
{
    public function __construct(
        private readonly ConversationEvent $model
    ) {}

    public function getForConversation(int $conversationId, int $limit = 50): Collection
    {
        return $this->model->with('createdBy')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): ConversationEvent
    {
        return $this->model->create($data);
    }
}
