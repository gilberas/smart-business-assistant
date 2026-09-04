<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'conversation_id' => $this->conversation_id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'content' => $this->content,
            'content_type' => $this->content_type,
            'status' => $this->status,
            'direction' => $this->direction,
            'message_type' => $this->message_type,
            'external_id' => $this->external_id,
            'metadata' => $this->metadata,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'message_attachments' => $this->whenLoaded('messageAttachments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
