<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'event_type' => $this->event_type ?? $this->type,
            'meta_event_id' => $this->meta_event_id,
            'status' => $this->status?->value ?? $this->status,
            'retry_count' => $this->retry_count,
            'last_error' => $this->last_error ?? $this->error_message,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
