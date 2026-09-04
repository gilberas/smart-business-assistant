<?php

namespace App\Models\AI;

use Database\Factories\AI\ProviderHealthFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderHealth extends Model
{
    /** @use HasFactory<ProviderHealthFactory> */
    use HasFactory;

    protected $table = 'provider_health';

    protected $fillable = [
        'ai_provider_id',
        'is_healthy',
        'latency_ms',
        'status_code',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_healthy' => 'boolean',
            'latency_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'ai_provider_id');
    }
}
