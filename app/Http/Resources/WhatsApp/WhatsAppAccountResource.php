<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsAppAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'business_id' => $this->business_id,
            'waba_id' => $this->waba_id,
            'token_status' => $this->token_status?->value ?? $this->token_status,
            'token_expires_at' => $this->token_expires_at?->toISOString(),
            'webhook_verified' => $this->webhook_verified_at !== null,
            'webhook_verified_at' => $this->webhook_verified_at?->toISOString(),
            'last_webhook_received_at' => $this->last_webhook_received_at?->toISOString(),
            'status' => $this->status?->value ?? $this->status,
            'connected_at' => $this->connected_at?->toISOString(),
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'phone_numbers' => PhoneNumberResource::collection($this->whenLoaded('phoneNumbers')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
