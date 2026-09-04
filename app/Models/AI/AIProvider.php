<?php

namespace App\Models\AI;

use Database\Factories\AI\AIProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIProvider extends Model
{
    /** @use HasFactory<AIProviderFactory> */
    use HasFactory;

    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'api_key',
        'is_active',
        'is_default',
        'priority',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(AIModel::class, 'ai_provider_id');
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(ProviderHealth::class, 'ai_provider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
