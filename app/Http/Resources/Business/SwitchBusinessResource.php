<?php

namespace App\Http\Resources\Business;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SwitchBusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'business' => new BusinessResource($this['business']),
            'user' => new UserResource($this['user']),
            'token' => $this['token'] ?? null,
        ];
    }
}
