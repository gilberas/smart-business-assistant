<?php

namespace App\Models;

use App\Enums\MembershipActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipActivity extends Model
{
    protected $table = 'membership_activity';

    protected $fillable = [
        'business_id',
        'user_id',
        'actor_id',
        'action',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public static function record(
        int $businessId,
        int $userId,
        ?int $actorId,
        MembershipActivityType $action,
        ?array $details = null,
    ): self {
        return static::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'action' => $action->value,
            'details' => $details,
        ]);
    }
}
