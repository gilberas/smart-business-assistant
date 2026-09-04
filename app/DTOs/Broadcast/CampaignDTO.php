<?php

namespace App\DTOs\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;

class CampaignDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $templateId,
        public readonly ?string $messageContent,
        public readonly array $recipientFilter,
        public readonly ?string $scheduledAt,
        public readonly BroadcastCampaignStatus $status = BroadcastCampaignStatus::Draft,
        public readonly array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            templateId: $data['template_id'] ?? null,
            messageContent: $data['message_content'] ?? null,
            recipientFilter: $data['recipient_filter'] ?? [],
            scheduledAt: $data['scheduled_at'] ?? null,
            status: BroadcastCampaignStatus::from($data['status'] ?? 'draft'),
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'template_id' => $this->templateId,
            'message_content' => $this->messageContent,
            'recipient_filter' => $this->recipientFilter,
            'scheduled_at' => $this->scheduledAt,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
        ];
    }
}
