<?php

namespace App\Models\RAG;

use App\Enums\RAG\IngestionStatus;
use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentIngestion extends Model
{
    protected $table = 'document_ingestions';

    protected $fillable = [
        'business_id',
        'ingestible_type',
        'ingestible_id',
        'status',
        'strategy',
        'total_chunks',
        'processed_chunks',
        'error_message',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => IngestionStatus::class,
            'metadata' => 'array',
            'total_chunks' => 'integer',
            'processed_chunks' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ingestible(): MorphTo
    {
        return $this->morphTo();
    }
}
