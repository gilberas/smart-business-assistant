<?php

namespace App\Models\AI;

use App\Models\Business;
use App\Models\User;
use Database\Factories\AI\AIUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsage extends Model
{
    /** @use HasFactory<AIUsageFactory> */
    use HasFactory;

    protected $table = 'ai_usage';

    protected $fillable = [
        'business_id',
        'user_id',
        'provider',
        'model',
        'date',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'total_duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'total_requests' => 'integer',
            'successful_requests' => 'integer',
            'failed_requests' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost' => 'float',
            'total_duration_ms' => 'integer',
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
