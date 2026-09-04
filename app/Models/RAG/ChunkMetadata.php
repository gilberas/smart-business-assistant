<?php

namespace App\Models\RAG;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChunkMetadata extends Model
{
    protected $table = 'chunk_metadata';

    protected $fillable = [
        'knowledge_chunk_id',
        'key',
        'value',
        'type',
    ];

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(KnowledgeChunk::class, 'knowledge_chunk_id');
    }
}
