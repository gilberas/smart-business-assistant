<?php

namespace App\Models\AI;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\User;
use Database\Factories\AI\AIRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIRequest extends Model
{
    /** @use HasFactory<AIRequestFactory> */
    use HasFactory;

    protected $table = 'ai_requests';

    protected $fillable = [
        'ai_configuration_id',
        'business_id',
        'user_id',
        'conversation_id',
        'provider',
        'model',
        'status',
        'finish_reason',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'duration_ms',
        'error_message',
        'request_data',
        'response_data',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost' => 'float',
            'duration_ms' => 'integer',
            'request_data' => 'array',
            'response_data' => 'array',
            'metadata' => 'array',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AIConfiguration::class, 'ai_configuration_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
