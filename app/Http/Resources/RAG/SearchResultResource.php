<?php

namespace App\Http\Resources\RAG;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'chunk_id' => $this->chunkId,
            'chunk_uuid' => $this->chunkUuid,
            'title' => $this->title,
            'content' => $this->content,
            'heading' => $this->heading,
            'relevance_score' => $this->relevanceScore,
            'ranking_breakdown' => $this->rankingBreakdown,
            'metadata' => $this->metadata,
            'category' => $this->category,
            'tags' => $this->tags,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }
}
