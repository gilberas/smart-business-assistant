<?php

namespace App\Http\Resources\RAG;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetrievalLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'query' => $this->query,
            'parsed_query' => $this->parsed_query,
            'intent' => $this->intent,
            'primary_strategy' => $this->primary_strategy,
            'total_results' => $this->total_results,
            'returned_results' => $this->returned_results,
            'execution_time_ms' => $this->execution_time_ms,
            'sources_used' => $this->sources_used,
            'context_token_estimate' => $this->context_token_estimate,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
