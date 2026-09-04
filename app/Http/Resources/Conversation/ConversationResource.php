<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label' => $this->status?->label(),
            'priority' => $this->priority instanceof \BackedEnum ? $this->priority->value : $this->priority,
            'priority_label' => $this->priority?->label(),
            'source' => $this->source instanceof \BackedEnum ? $this->source->value : $this->source,
            'source_label' => $this->source?->label(),
            'channel' => $this->channel,
            'channel_id' => $this->channel_id,
            'handover_mode' => $this->handover_mode,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'labels' => ConversationLabelResource::collection($this->whenLoaded('labels')),
            'participants' => ConversationParticipantResource::collection($this->whenLoaded('activeParticipants')),
            'last_message' => $this->whenLoaded('lastMessage', fn () => [
                'id' => $this->lastMessage?->id,
                'content' => $this->lastMessage?->content,
                'content_type' => $this->lastMessage?->content_type,
                'sender_type' => $this->lastMessage?->sender_type,
                'created_at' => $this->lastMessage?->created_at,
            ]),
            'message_count' => $this->message_count,
            'last_message_at' => $this->last_message_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
