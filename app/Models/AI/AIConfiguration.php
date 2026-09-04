<?php

namespace App\Models\AI;

use App\Models\Business;
use App\Models\User;
use Database\Factories\AI\AIConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIConfiguration extends Model
{
    /** @use HasFactory<AIConfigurationFactory> */
    use HasFactory;

    protected $table = 'ai_configurations';

    protected $fillable = [
        'business_id',
        'provider',
        'model',
        'temperature',
        'top_p',
        'max_tokens',
        'timeout_seconds',
        'streaming',
        'language',
        'safety_settings',
        'system_prompt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'top_p' => 'float',
            'max_tokens' => 'integer',
            'timeout_seconds' => 'integer',
            'streaming' => 'boolean',
            'safety_settings' => 'array',
            'system_prompt' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
