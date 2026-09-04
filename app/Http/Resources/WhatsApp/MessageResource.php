<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'business_id' => $this->business_id,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'direction' => $this->direction?->value ?? $this->direction,
            'message_type' => $this->message_type?->value ?? $this->message_type,
            'meta_message_id' => $this->meta_message_id,
            'conversation_id' => $this->conversation_id,
            'status' => $this->status?->value ?? $this->status,
            'body' => $this->body,
            'media_id' => $this->media_id,
            'caption' => $this->caption,
            'template_name' => $this->template_name,
            'context' => $this->context,
            'errors' => $this->errors,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'statuses' => MessageStatusResource::collection($this->whenLoaded('statuses')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
