<?php

namespace App\Repositories\WhatsApp;

use App\Enums\WhatsApp\MessageDirection;
use App\Enums\WhatsApp\MessageStatus;
use App\Models\WhatsApp\Message;

class MessageRepository
{
    public function findById(int $id): ?Message
    {
        return Message::find($id);
    }

    public function findByUuid(string $uuid): ?Message
    {
        return Message::where('uuid', $uuid)->first();
    }

    public function findByMetaMessageId(string $metaMessageId): ?Message
    {
        return Message::where('meta_message_id', $metaMessageId)->first();
    }

    public function getByBusinessId(int $businessId, array $filters = [], int $perPage = 15): mixed
    {
        $query = Message::where('business_id', $businessId);

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['message_type'])) {
            $query->where('message_type', $filters['message_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_number'])) {
            $query->where('from_number', 'like', '%' . $filters['from_number'] . '%');
        }

        if (!empty($filters['to_number'])) {
            $query->where('to_number', 'like', '%' . $filters['to_number'] . '%');
        }

        if (!empty($filters['conversation_id'])) {
            $query->where('conversation_id', $filters['conversation_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getConversation(int $businessId, string $fromNumber, int $limit = 50): iterable
    {
        return Message::where('business_id', $businessId)
            ->where(function ($q) use ($fromNumber) {
                $q->where('from_number', $fromNumber)
                    ->orWhere('to_number', $fromNumber);
            })
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function update(Message $message, array $data): Message
    {
        $message->update($data);
        return $message->fresh();
    }

    public function delete(Message $message): void
    {
        $message->delete();
    }

    public function markAsSent(Message $message): Message
    {
        return $this->update($message, [
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(Message $message): Message
    {
        return $this->update($message, [
            'status' => MessageStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(Message $message): Message
    {
        return $this->update($message, [
            'status' => MessageStatus::Read,
            'read_at' => now(),
        ]);
    }

    public function markAsFailed(Message $message, ?array $errors = null): Message
    {
        return $this->update($message, [
            'status' => MessageStatus::Failed,
            'failed_at' => now(),
            'errors' => $errors,
        ]);
    }

    public function getPendingOutbound(int $limit = 50): iterable
    {
        return Message::outbound()
            ->where('status', MessageStatus::Pending)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function countByBusinessId(int $businessId): array
    {
        return [
            'total' => Message::where('business_id', $businessId)->count(),
            'inbound' => Message::where('business_id', $businessId)->inbound()->count(),
            'outbound' => Message::where('business_id', $businessId)->outbound()->count(),
            'sent' => Message::where('business_id', $businessId)->where('status', MessageStatus::Sent)->count(),
            'delivered' => Message::where('business_id', $businessId)->where('status', MessageStatus::Delivered)->count(),
            'read' => Message::where('business_id', $businessId)->where('status', MessageStatus::Read)->count(),
            'failed' => Message::where('business_id', $businessId)->where('status', MessageStatus::Failed)->count(),
        ];
    }
}
