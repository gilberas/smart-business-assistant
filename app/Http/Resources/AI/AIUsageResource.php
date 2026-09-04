<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'provider' => $this->provider,
            'model' => $this->model,
            'date' => $this->date?->toDateString(),
            'total_requests' => $this->total_requests,
            'successful_requests' => $this->successful_requests,
            'failed_requests' => $this->failed_requests,
            'prompt_tokens' => $this->prompt_tokens,
            'completion_tokens' => $this->completion_tokens,
            'total_tokens' => $this->total_tokens,
            'estimated_cost' => $this->estimated_cost,
            'total_duration_ms' => $this->total_duration_ms,
        ];
    }
}
