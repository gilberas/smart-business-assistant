<?php

namespace App\Repositories\Conversation;

use App\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageRepository
{
    public function __construct(
        private readonly Message $model
    ) {}

    public function findById(int $id): ?Message
    {
        return $this->model->with(['attachments', 'messageAttachments'])->find($id);
    }

    public function getForConversation(int $conversationId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model->with(['attachments', 'messageAttachments'])
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    public function getRecentForConversation(int $conversationId, int $limit = 50): array
    {
        return $this->model->with(['attachments', 'messageAttachments'])
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->toArray();
    }

    public function create(array $data): Message
    {
        return $this->model->create($data);
    }

    public function createForConversation(int $conversationId, array $data): Message
    {
        $data['conversation_id'] = $conversationId;
        return $this->model->create($data);
    }

    public function update(Message $message, array $data): bool
    {
        return $message->update($data);
    }

    public function countForBusiness(int $businessId, array $filters = []): int
    {
        $query = $this->model->where('business_id', $businessId);

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['since'])) {
            $query->where('created_at', '>=', $filters['since']);
        }

        if (!empty($filters['until'])) {
            $query->where('created_at', '<=', $filters['until']);
        }

        return $query->count();
    }
}
