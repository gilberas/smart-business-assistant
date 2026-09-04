<?php

namespace App\Repositories\WhatsApp;

use App\Models\WhatsApp\MessageStatus;

class MessageStatusRepository
{
    public function create(array $data): MessageStatus
    {
        return MessageStatus::create($data);
    }

    public function getByMessageId(int $messageId): iterable
    {
        return MessageStatus::where('message_id', $messageId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getLatestByMessageId(int $messageId): ?MessageStatus
    {
        return MessageStatus::where('message_id', $messageId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
