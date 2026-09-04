<?php

namespace App\Http\Resources\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignListResource extends JsonResource
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
            'template_name' => $this->whenLoaded('template', fn() => $this->template?->name),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'creator_name' => $this->whenLoaded('creator', fn() => $this->creator?->name),
            'recipients_count' => (int) ($this->recipients_count ?? $this->whenCounted('recipients', 0)),
            'estimated_recipients' => $this->metadata['estimated_recipients'] ?? null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
