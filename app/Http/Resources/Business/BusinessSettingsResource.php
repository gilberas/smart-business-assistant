<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'business_hours' => $this->business_hours,
            'ai_provider' => $this->ai_provider,
            'ai_model' => $this->ai_model,
            'ai_temperature' => (float) $this->ai_temperature,
            'ai_max_tokens' => $this->ai_max_tokens,
            'ai_system_prompt' => $this->ai_system_prompt,
            'settings' => $this->settings,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
