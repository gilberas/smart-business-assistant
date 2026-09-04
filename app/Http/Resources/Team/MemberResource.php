<?php

namespace App\Http\Resources\Team;

use Carbon\Carbon;
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
            'phone' => $this->phone,
            'role' => $this->pivot->role,
            'status' => $this->pivot->status,
            'joined_at' => $this->pivot->joined_at ? Carbon::parse($this->pivot->joined_at)->toISOString() : null,
            'last_switched_at' => $this->pivot->last_switched_at ? Carbon::parse($this->pivot->last_switched_at)->toISOString() : null,
            'is_owner' => $this->id === ($this->pivot?->business?->owner_id ?? optional($this->business)->owner_id),
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }
}
