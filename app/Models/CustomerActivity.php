<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerActivity extends Model
{
    const UPDATED_AT = null;

    protected $table = 'customer_activity';

    protected $fillable = [
        'business_id',
        'customer_id',
        'actor_id',
        'type',
        'label',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(
        int $businessId,
        int $customerId,
        ActivityType $type,
        ?int $actorId = null,
        ?string $label = null,
        ?array $metadata = null,
    ): self {
        return static::create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'actor_id' => $actorId,
            'type' => $type->value,
            'label' => $label,
            'metadata' => $metadata,
        ]);
    }
}
