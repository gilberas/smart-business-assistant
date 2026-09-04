<?php

namespace App\Models\RAG;

use App\Models\Business;
use Database\Factories\RAG\KnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class KnowledgeChunk extends Model
{
    /** @use HasFactory<KnowledgeChunkFactory> */
    use HasFactory;

    protected $table = 'knowledge_chunks';

    protected $fillable = [
        'business_id',
        'uuid',
        'chunkable_type',
        'chunkable_id',
        'source_type',
        'source_id',
        'chunk_index',
        'strategy',
        'content',
        'heading',
        'metadata',
        'word_count',
        'char_count',
        'token_estimate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'word_count' => 'integer',
            'char_count' => 'integer',
            'token_estimate' => 'integer',
            'chunk_index' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgeChunk $chunk) {
            if (empty($chunk->uuid)) {
                $chunk->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function chunkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function metadataRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChunkMetadata::class, 'knowledge_chunk_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySource($query, string $sourceType, ?int $sourceId = null)
    {
        $query->where('source_type', $sourceType);
        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }
        return $query;
    }

    public function scopeByBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
