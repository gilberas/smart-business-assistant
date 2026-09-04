<?php

namespace App\Models\AI;

use Database\Factories\AI\AIModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIModel extends Model
{
    /** @use HasFactory<AIModelFactory> */
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'ai_provider_id',
        'model_id',
        'name',
        'group',
        'capabilities',
        'input_price_per_1k',
        'output_price_per_1k',
        'context_window',
        'max_output_tokens',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'input_price_per_1k' => 'float',
            'output_price_per_1k' => 'float',
            'context_window' => 'integer',
            'max_output_tokens' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'ai_provider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
    }
}
