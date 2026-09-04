<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'max_tokens' => $this->max_tokens,
            'timeout_seconds' => $this->timeout_seconds,
            'streaming' => $this->streaming,
            'language' => $this->language,
            'safety_settings' => $this->safety_settings,
            'system_prompt' => $this->system_prompt,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
