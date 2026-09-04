<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\Business\BusinessResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this['user']),
            'token' => $this['token'],
            'current_business' => !empty($this['current_business'])
                ? new BusinessResource($this['current_business'])
                : null,
        ];
    }
}
