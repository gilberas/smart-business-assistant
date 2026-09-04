<?php

namespace App\DTOs\RAG;

readonly class SearchResultDTO
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public ?int $chunkId = null,
        public ?string $chunkUuid = null,
        public string $title = '',
        public string $content = '',
        public ?string $heading = null,
        public ?string $url = null,
        public float $relevanceScore = 0.0,
        public array $rankingBreakdown = [],
        public array $metadata = [],
        public ?string $category = null,
        public array $tags = [],
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'chunk_id' => $this->chunkId,
            'chunk_uuid' => $this->chunkUuid,
            'title' => $this->title,
            'content' => $this->content,
            'heading' => $this->heading,
            'url' => $this->url,
            'relevance_score' => round($this->relevanceScore, 4),
            'ranking_breakdown' => $this->rankingBreakdown,
            'metadata' => $this->metadata,
            'category' => $this->category,
            'tags' => $this->tags,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }
}
