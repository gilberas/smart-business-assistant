<?php

namespace App\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status instanceof \App\Enums\Broadcast\BroadcastRecipientStatus
                ? $this->status->value
                : $this->status,
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'uuid' => $this->customer->uuid,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
        ];
    }
}
