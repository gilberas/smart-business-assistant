<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'direction' => $this->direction?->value ?? $this->direction,
            'message_type' => $this->message_type?->value ?? $this->message_type,
            'status' => $this->status?->value ?? $this->status,
            'body' => $this->body ? substr($this->body, 0, 200) : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
