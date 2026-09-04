<?php

namespace App\Http\Resources\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'status' => $this->status instanceof BroadcastCampaignStatus
                ? $this->status->value
                : $this->status,
            'status_label' => $this->status instanceof BroadcastCampaignStatus
                ? $this->status->label()
                : $this->status,
            'template' => $this->whenLoaded('template', fn() => new TemplateResource($this->template)),
            'template_id' => $this->template_id,
            'message_content' => $this->message_content,
            'recipient_filter' => $this->recipient_filter,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'recipients_count' => (int) ($this->recipients_count ?? $this->whenCounted('recipients', 0)),
            'estimated_recipients' => $this->metadata['estimated_recipients'] ?? null,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
