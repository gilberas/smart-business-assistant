<?php

namespace App\Services\Conversation;

use App\Enums\Conversation\ConversationStatus;
use App\Events\Conversation\ConversationAssigned;
use App\Events\Conversation\ConversationClosed;
use App\Events\Conversation\ConversationCreated;
use App\Events\Conversation\ConversationReopened;
use App\Events\Conversation\ConversationUpdated;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Conversation\ConversationEventRepository;
use App\Repositories\Conversation\ConversationLabelRepository;
use App\Repositories\Conversation\ConversationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepository $repository,
        private readonly ConversationEventRepository $eventRepository,
        private readonly ConversationLabelRepository $labelRepository,
    ) {}

    public function list(int $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForBusiness($businessId, $filters, $perPage);
    }

    public function find(int $id): ?Conversation
    {
        return $this->repository->findById($id);
    }

    public function findByUuid(string $uuid): ?Conversation
    {
        return $this->repository->findByUuid($uuid);
    }

    public function findForBusiness(int $businessId, string $uuid): ?Conversation
    {
        return $this->repository->findForBusiness($businessId, $uuid);
    }

    public function create(array $data): Conversation
    {
        $conversation = $this->repository->create($data);

        $this->eventRepository->create([
            'conversation_id' => $conversation->id,
            'type' => 'created',
            'created_at' => now(),
        ]);

        event(new ConversationCreated($conversation));

        return $conversation;
    }

    public function update(Conversation $conversation, array $data): bool
    {
        $changed = $this->repository->update($conversation, $data);

        if ($changed) {
            $this->eventRepository->create([
                'conversation_id' => $conversation->id,
                'type' => 'updated',
                'payload' => ['changes' => array_keys($data)],
                'created_at' => now(),
            ]);

            event(new ConversationUpdated($conversation));
        }

        return $changed;
    }

    public function assign(Conversation $conversation, User $user, ?string $reason = null): void
    {
        $previousAssignee = $conversation->assigned_to;
        $conversation->assignTo($user, $reason);

        event(new ConversationAssigned($conversation, $user, $previousAssignee, $reason));
    }

    public function close(Conversation $conversation, ?string $reason = null): void
    {
        $conversation->close($reason);

        event(new ConversationClosed($conversation, $reason));
    }

    public function reopen(Conversation $conversation): void
    {
        $conversation->reopen();

        event(new ConversationReopened($conversation));
    }

    public function delete(Conversation $conversation): bool
    {
        return $this->repository->delete($conversation);
    }

    public function getLabels(int $businessId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->labelRepository->getForBusiness($businessId);
    }

    public function createLabel(int $businessId, array $data): \App\Models\ConversationLabel
    {
        return $this->labelRepository->create(array_merge($data, ['business_id' => $businessId]));
    }

    public function attachLabel(Conversation $conversation, int $labelId): void
    {
        $conversation->labels()->syncWithoutDetaching([$labelId]);

        $this->eventRepository->create([
            'conversation_id' => $conversation->id,
            'type' => 'label_added',
            'payload' => ['label_id' => $labelId],
            'created_at' => now(),
        ]);
    }

    public function detachLabel(Conversation $conversation, int $labelId): void
    {
        $conversation->labels()->detach($labelId);

        $this->eventRepository->create([
            'conversation_id' => $conversation->id,
            'type' => 'label_removed',
            'payload' => ['label_id' => $labelId],
            'created_at' => now(),
        ]);
    }

    public function stats(int $businessId): array
    {
        return [
            'by_status' => $this->repository->countByStatus($businessId),
            'by_channel' => $this->repository->countByChannel($businessId),
        ];
    }
}
