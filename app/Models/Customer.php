<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'uuid',
        'name',
        'phone',
        'phone_alt',
        'email',
        'status',
        'source',
        'preferred_language',
        'timezone',
        'customer_type',
        'lifecycle_stage',
        'company_name',
        'job_title',
        'website_url',
        'bio',
        'avatar_url',
        'social_instagram',
        'social_facebook',
        'social_twitter',
        'social_linkedin',
        'gender',
        'birthday',
        'nationality',
        'metadata',
        'total_orders',
        'total_spent',
        'average_order_value',
        'loyalty_points',
        'loyalty_tier',
        'assigned_to',
        'last_interacted_at',
        'last_activity_at',
    ];

        public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'birthday' => 'date',
            'last_interacted_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'loyalty_points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_customer')
            ->withTimestamps();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function pinnedNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->where('is_pinned', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_primary', true);
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomerCustomFieldValue::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(CustomerActivity::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByLifecycleStage($query, string $stage)
    {
        return $query->where('lifecycle_stage', $stage);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('company_name', 'like', "%{$term}%");
        });
    }

    public function scopeOrdered($query, string $sort = 'created_at', string $direction = 'desc')
    {
        return $query->orderBy($sort, $direction);
    }
}
