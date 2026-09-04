<?php

namespace App\Repositories\Conversation;

use App\Models\RoutingHistory;
use Illuminate\Database\Eloquent\Collection;

class RoutingHistoryRepository
{
    public function __construct(
        private readonly RoutingHistory $model
    ) {}

    public function getForConversation(int $conversationId): Collection
    {
        return $this->model->with('routedBy')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): RoutingHistory
    {
        return $this->model->create($data);
    }
}
