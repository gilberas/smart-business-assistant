<?php

namespace App\Models\RAG;

use App\Models\Business;
use App\Models\User;
use Database\Factories\RAG\RetrievalLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetrievalLog extends Model
{
    /** @use HasFactory<RetrievalLogFactory> */
    use HasFactory;
    protected $table = 'retrieval_logs';

    protected $fillable = [
        'business_id',
        'user_id',
        'session_id',
        'query',
        'parsed_query',
        'intent',
        'primary_strategy',
        'total_results',
        'returned_results',
        'execution_time_ms',
        'sources_used',
        'top_chunk_ids',
        'context_token_estimate',
    ];

    protected function casts(): array
    {
        return [
            'parsed_query' => 'array',
            'sources_used' => 'array',
            'top_chunk_ids' => 'array',
            'execution_time_ms' => 'float',
            'total_results' => 'integer',
            'returned_results' => 'integer',
            'context_token_estimate' => 'float',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
