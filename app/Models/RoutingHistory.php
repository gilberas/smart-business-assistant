<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'routing_history';

    protected $fillable = [
        'conversation_id',
        'from_type',
        'to_type',
        'from_id',
        'to_id',
        'reason',
        'routed_by',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by');
    }
}
