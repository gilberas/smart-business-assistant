<?php

namespace App\Repositories\WhatsApp;

use App\Enums\WhatsApp\WebhookEventStatus;
use App\Models\WhatsApp\WebhookEvent;

class WebhookEventRepository
{
    public function findById(int $id): ?WebhookEvent
    {
        return WebhookEvent::find($id);
    }

    public function findByUuid(string $uuid): ?WebhookEvent
    {
        return WebhookEvent::where('uuid', $uuid)->first();
    }

    public function findByMetaEventId(string $metaEventId): ?WebhookEvent
    {
        return WebhookEvent::where('meta_event_id', $metaEventId)->first();
    }

    public function create(array $data): WebhookEvent
    {
        return WebhookEvent::create($data);
    }

    public function update(WebhookEvent $event, array $data): WebhookEvent
    {
        $event->update($data);
        return $event->fresh();
    }

    public function getPending(int $limit = 50): iterable
    {
        return WebhookEvent::pending()
            ->where('retry_count', '<', 5)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function getFailed(int $limit = 50): iterable
    {
        return WebhookEvent::failed()
            ->where('retry_count', '<', 5)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function getDeadLetters(): iterable
    {
        return WebhookEvent::where('status', WebhookEventStatus::DeadLetter)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function countByBusinessAndStatus(int $businessId, string $status): int
    {
        return WebhookEvent::where('business_id', $businessId)
            ->where('status', $status)
            ->count();
    }

    public function markAsProcessing(WebhookEvent $event): WebhookEvent
    {
        return $this->update($event, ['status' => WebhookEventStatus::Processing]);
    }

    public function markAsProcessed(WebhookEvent $event): WebhookEvent
    {
        return $this->update($event, [
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(WebhookEvent $event, string $error): WebhookEvent
    {
        return $this->update($event, [
            'status' => $event->retry_count >= 4 ? WebhookEventStatus::DeadLetter : WebhookEventStatus::Failed,
            'last_error' => $error,
            'retry_count' => $event->retry_count + 1,
        ]);
    }
}
