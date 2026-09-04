<?php

namespace App\Repositories\Conversation;

use App\Enums\Conversation\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Pagination\LengthAwarePaginator;

class ConversationRepository
{
    public function __construct(
        private readonly Conversation $model
    ) {}

    public function findById(int $id): ?Conversation
    {
        return $this->model->with(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage'])->find($id);
    }

    public function findByUuid(string $uuid): ?Conversation
    {
        return $this->model->with(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage'])->where('uuid', $uuid)->first();
    }

    public function findForBusiness(int $businessId, string $uuid): ?Conversation
    {
        return $this->model->with(['customer', 'assignedTo', 'labels', 'activeParticipants.user', 'lastMessage'])
            ->where('business_id', $businessId)
            ->where('uuid', $uuid)
            ->first();
    }

    public function getForBusiness(int $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['customer', 'assignedTo', 'labels', 'lastMessage'])
            ->where('business_id', $businessId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['unassigned'])) {
            $query->whereNull('assigned_to');
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['label_id'])) {
            $query->whereHas('labels', fn ($q) => $q->where('conversation_labels.id', $filters['label_id']));
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['active'])) {
            $query->active();
        }

        $sortField = $filters['sort'] ?? 'last_message_at';
        $sortDir = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage);
    }

    public function create(array $data): Conversation
    {
        return $this->model->create($data);
    }

    public function update(Conversation $conversation, array $data): bool
    {
        return $conversation->update($data);
    }

    public function delete(Conversation $conversation): bool
    {
        return $conversation->delete();
    }

    public function countByStatus(int $businessId): array
    {
        return $this->model->where('business_id', $businessId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function countByChannel(int $businessId): array
    {
        return $this->model->where('business_id', $businessId)
            ->selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();
    }
}
