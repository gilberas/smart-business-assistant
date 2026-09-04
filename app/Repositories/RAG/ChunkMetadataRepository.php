<?php

namespace App\Repositories\RAG;

use App\Models\RAG\ChunkMetadata;
use Illuminate\Database\Eloquent\Collection;

class ChunkMetadataRepository
{
    public function __construct(
        private readonly ChunkMetadata $model,
    ) {}

    public function findByChunk(int $chunkId): Collection
    {
        return $this->model->where('knowledge_chunk_id', $chunkId)->get();
    }

    public function findByKey(int $chunkId, string $key): ?ChunkMetadata
    {
        return $this->model->where('knowledge_chunk_id', $chunkId)
            ->where('key', $key)
            ->first();
    }

    public function create(array $data): ChunkMetadata
    {
        return $this->model->create($data);
    }

    public function updateOrCreate(int $chunkId, string $key, mixed $value, string $type = 'string'): ChunkMetadata
    {
        return $this->model->updateOrCreate(
            ['knowledge_chunk_id' => $chunkId, 'key' => $key],
            ['value' => (string) $value, 'type' => $type],
        );
    }

    public function deleteByChunk(int $chunkId): bool
    {
        return $this->model->where('knowledge_chunk_id', $chunkId)->delete() > 0;
    }
}
