<?php

namespace App\Http\Resources\Business;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'role' => $this->pivot->role,
            'status' => $this->pivot->status,
            'joined_at' => $this->pivot->joined_at?->toISOString(),
            'last_switched_at' => $this->pivot->last_switched_at?->toISOString(),
        ];
    }
}
