<?php

namespace App\Http\Resources\Conversation;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->role instanceof \BackedEnum ? $this->role->value : $this->role,
            'joined_at' => $this->joined_at,
        ];
    }
}
