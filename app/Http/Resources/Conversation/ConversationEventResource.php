<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type;
        if ($type instanceof \BackedEnum) {
            $type = $type->value;
        }

        return [
            'id' => $this->id,
            'type' => $type,
            'payload' => $this->payload,
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'created_at' => $this->created_at,
        ];
    }
}
