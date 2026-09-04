<?php

namespace App\Models;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BroadcastCampaign extends Model
{
    protected $fillable = [
        'business_id',
        'uuid',
        'name',
        'status',
        'template_id',
        'message_content',
        'recipient_filter',
        'scheduled_at',
        'started_at',
        'completed_at',
        'sent_at',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'recipient_filter' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
            'status' => BroadcastCampaignStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class, 'campaign_id');
    }
}
