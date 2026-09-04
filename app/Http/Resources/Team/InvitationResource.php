<?php

namespace App\Http\Resources\Team;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'email' => $this->email,
            'role' => $this->role,
            'token' => $this->token,
            'status' => $this->status,
            'message' => $this->message,
            'expires_at' => $this->expires_at->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'inviter' => $this->when($this->relationLoaded('inviter') && $this->inviter, fn() => [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
            ]),
            'user' => $this->when($this->relationLoaded('user') && $this->user, fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
