<?php

namespace App\Http\Resources\RAG;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChunkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'business_id' => $this->business_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'chunk_index' => $this->chunk_index,
            'strategy' => $this->strategy,
            'content' => $this->content,
            'heading' => $this->heading,
            'metadata' => $this->metadata,
            'word_count' => $this->word_count,
            'char_count' => $this->char_count,
            'token_estimate' => $this->token_estimate,
            'is_active' => $this->is_active,
            'chunkable_type' => $this->chunkable_type,
            'chunkable_id' => $this->chunkable_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
