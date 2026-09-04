<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->ai_provider_id,
            'model_id' => $this->model_id,
            'name' => $this->name,
            'group' => $this->group,
            'capabilities' => $this->capabilities,
            'input_price' => $this->input_price_per_1k,
            'output_price' => $this->output_price_per_1k,
            'context_window' => $this->context_window,
            'max_output_tokens' => $this->max_output_tokens,
            'is_active' => $this->is_active,
        ];
    }
}
