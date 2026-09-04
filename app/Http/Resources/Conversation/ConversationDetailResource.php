<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label' => $this->status?->label(),
            'status_active' => $this->isActive(),
            'priority' => $this->priority instanceof \BackedEnum ? $this->priority->value : $this->priority,
            'priority_label' => $this->priority?->label(),
            'source' => $this->source instanceof \BackedEnum ? $this->source->value : $this->source,
            'source_label' => $this->source?->label(),
            'channel' => $this->channel,
            'channel_id' => $this->channel_id,
            'handover_mode' => $this->handover_mode,
            'handover_reason' => $this->handover_reason,
            'handovered_at' => $this->handovered_at,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'labels' => ConversationLabelResource::collection($this->whenLoaded('labels')),
            'participants' => ConversationParticipantResource::collection($this->whenLoaded('activeParticipants')),
            'message_count' => $this->message_count,
            'last_message_at' => $this->last_message_at,
            'closed_at' => $this->closed_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
